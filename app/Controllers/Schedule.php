<?php
namespace App\Controllers;
use App\Models\UserServiceModel;
use App\Models\GoogleScheduleModel;
use App\Models\SubjectModel;
use App\Libraries\GoogleCalendarClient;
use App\Libraries\GoogleCalendarOAuth;
use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;
class Schedule extends BaseController
{
    protected string $schedulePath = 'schedule';
    protected string $configurationPath = 'tools/googleSchedule';
    protected bool $readStoredForUser = true;

    protected function active(): ?array
    {
        return (new UserServiceModel())->googleConnection((array) session('auth_user'));
    }
    protected function syncConnections(array &$errors = []): array
    {
        $services = [];
        try {
            $service = $this->active();
            if ($service !== null) $services[] = $service;
        } catch (Throwable $exception) { $errors[] = $this->syncError($exception); }
        if ($this->readStoredForUser) {
            try {
                $private = (new GoogleCalendarOAuth())->access((array) session('auth_user'));
                if ($private !== null) $services[] = $private;
            } catch (Throwable $exception) { $errors[] = $this->syncError($exception); }
        }
        return $services;
    }
    protected function syncError(Throwable $exception, string $stage = 'connection'): string
    {
        $reference = bin2hex(random_bytes(4));
        log_message('error', 'Schedule synchronization failed: ref={ref} stage={stage} type={type} code={code} file={file} line={line}', [
            'ref' => $reference, 'stage' => $stage, 'type' => $exception::class, 'code' => $exception->getCode(),
            'file' => $exception->getFile(), 'line' => $exception->getLine(),
        ]);
        return $exception instanceof \App\Libraries\GoogleCalendarException
            ? $exception->getMessage() : ($stage === 'storage'
                ? 'Os eventos foram recebidos do Google, mas não puderam ser salvos. Verifique as migrações do banco no servidor (php spark migrate).'
                : 'Não foi possível atualizar uma das agendas. Os dados anteriores foram mantidos.') . ' Referência: ' . $reference . '.';
    }
    protected function collect(array $services, GoogleScheduleModel $model, GoogleCalendarClient $client, array &$errors): int
    {
        $count = 0;
        foreach ($services as $service) {
            session()->set('schedule_attempt_' . $service['id'] . '_' . sha1($service['email']), time());
            try {
                $events = $client->fetch($service);
            } catch (Throwable $exception) {
                $errors[] = $this->syncError($exception, 'fetch');
                continue;
            }
            try {
                $model->replaceSnapshot($service, $events);
                $count += count($events);
            } catch (Throwable $exception) { $errors[] = $this->syncError($exception, 'storage'); }
        }
        return $count;
    }
    protected function dueConnections(array $services, GoogleScheduleModel $model, string $userId): array
    {
        return array_values(array_filter($services, static function (array $service) use ($model, $userId): bool {
            $last = $model->selectMax('synced_at')->where('service_id', $service['id'])
                ->where('user_id', $userId)->where('calendar_id', $service['email'])->first()['synced_at'] ?? null;
            $attempt = (int) session()->get('schedule_attempt_' . $service['id'] . '_' . sha1($service['email']));
            return ($last === null || strtotime($last . ' UTC') < time() - 300) && $attempt < time() - 60;
        }));
    }
    public function index()
    {
        // Always render the snapshot written by the latest Google synchronization.
        $this->response->setHeader('Cache-Control', 'private, no-store, max-age=0');
        if ($this->readStoredForUser) {
            $actor = (array) session('auth_user');
            $userId = (string) ($actor['id'] ?? '');
            if ($userId === '') throw PageNotFoundException::forPageNotFound();
            $model = new GoogleScheduleModel();
            $attemptKey = 'schedule_user_attempt_' . sha1($userId);
            if ((int) session()->get($attemptKey) < time() - 60) {
                session()->set($attemptKey, time());
                $errors = [];
                $services = $this->syncConnections($errors);
                $due = $this->dueConnections($services, $model, $userId);
                $this->collect($due, $model, new GoogleCalendarClient(), $errors);
                if ($errors !== []) session()->setFlashdata('error', implode(' ', array_unique($errors)));
            }
            return view('main', ['content' => view('schedule/index', [
                'schedulePath' => $this->schedulePath, 'configurationPath' => $this->configurationPath,
                'events' => $model->upcomingForUser($userId)->paginate(25), 'pager' => $model->pager,
                'lastSync' => $model->latestSyncForUser($userId),
                'subjects' => (new SubjectModel())->forUser($actor),
            ])]);
        }
        try { $service = $this->active(); }
        catch (Throwable $exception) {
            return redirect()->to(site_url($this->configurationPath))->with('error', $exception instanceof \App\Libraries\GoogleCalendarException ? $exception->getMessage() : 'Não foi possível ler a configuração do Google Agenda.');
        }
        if ($service === null) return redirect()->to(site_url($this->configurationPath))->with('error', 'Configure e autorize o serviço Google Agenda para acessar a agenda.');
        $model = new GoogleScheduleModel();
        $lastSync = $model->selectMax('synced_at')->where('service_id', $service['id'])
            ->where('user_id', $service['user_id'])->where('calendar_id', $service['email'])->first()['synced_at'] ?? null;
        $attemptKey = 'schedule_attempt_' . $service['id'] . '_' . sha1($service['email']);
        $lastAttempt = (int) session()->get($attemptKey);
        if (($lastSync === null || strtotime($lastSync . ' UTC') < time() - 300) && $lastAttempt < time() - 60) {
            session()->set($attemptKey, time());
            try {
                $model->replaceSnapshot($service, (new GoogleCalendarClient())->fetch($service));
                $lastSync = gmdate('Y-m-d H:i:s');
            } catch (Throwable $exception) {
                session()->setFlashdata('error', $exception instanceof \App\Libraries\GoogleCalendarException
                    ? $exception->getMessage() : 'Não foi possível consultar a agenda.');
            }
        }
        $events = $model->upcoming($service)->paginate(25);
        if ($events !== [] && !session()->getFlashdata('error')
            && count(array_filter($events, static fn (array $event): bool =>
                trim((string) $event['title']) === '' || $event['title'] === 'Sem título')) === count($events)) {
            session()->setFlashdata('error', 'O Google não retornou os títulos destas reuniões. Verifique o acesso aos detalhes da agenda; agendas privadas exigem autorização OAuth.');
        }
        return view('main', ['content' => view('schedule/index', [
            'schedulePath' => $this->schedulePath, 'configurationPath' => $this->configurationPath,
            'events' => $events, 'pager' => $model->pager, 'lastSync' => $lastSync,
            'subjects' => (new SubjectModel())->forUser((array) session('auth_user')),
        ])]);
    }
    public function sync()
    {
        $this->response->setHeader('Cache-Control', 'private, no-store, max-age=0');
        try {
            $errors = [];
            $services = $this->syncConnections($errors);
            if ($services === [] && $errors === []) return redirect()->to(site_url($this->configurationPath))->with('error', 'Cadastre e autorize um serviço Google Agenda.');
            $eventCount = $this->collect($services, new GoogleScheduleModel(), new GoogleCalendarClient(), $errors);
            if ($errors !== []) return redirect()->to(site_url($this->schedulePath))->with('error',
                $eventCount . ' evento(s) atualizado(s) na agenda local. ' . implode(' ', array_unique($errors)));
            return redirect()->to(site_url($this->schedulePath))->with(
                'success',
                'Agenda sincronizada com o Google: ' . $eventCount . ' evento(s) recebido(s).'
            );
        } catch (Throwable $exception) {
            return redirect()->to(site_url($this->schedulePath))->with('error', $exception instanceof \App\Libraries\GoogleCalendarException
                ? $exception->getMessage() : 'Não foi possível atualizar a agenda.');
        }
    }
    public function subject(int $id)
    {
        try {
            $value = $this->request->getPost('subject_id');
            if (!is_string($value) || ($value !== '' && (!ctype_digit($value) || (int) $value < 1))) {
                return redirect()->to(site_url($this->schedulePath))->with('error', 'Selecione um assunto válido.');
            }
            $model = new GoogleScheduleModel();
            if ($this->readStoredForUser) {
                $userId = (string) (((array) session('auth_user'))['id'] ?? '');
                if ($userId === '') throw PageNotFoundException::forPageNotFound();
                $saved = $model->assignSubjectForUser($id, $value === '' ? null : (int) $value, $userId);
            } else {
                $service = $this->active();
                if ($service === null) return redirect()->to(site_url($this->configurationPath));
                $saved = $model->assignSubject($id, $value === '' ? null : (int) $value, $service);
            }
            if (!$saved) {
                throw new \RuntimeException();
            }
            return redirect()->to(site_url($this->schedulePath))->with('success', 'Assunto da reunião atualizado.');
        } catch (PageNotFoundException $exception) { throw $exception; }
        catch (Throwable $exception) {
            return redirect()->to(site_url($this->schedulePath))->with('error', 'Não foi possível atualizar o assunto.');
        }
    }
}
