<?php
namespace App\Controllers;
use App\Models\UserServiceModel;
use App\Models\GoogleScheduleModel;
use App\Models\SubjectModel;
use App\Libraries\GoogleCalendarClient;
use CodeIgniter\Exceptions\PageNotFoundException;
use Throwable;
class Schedule extends BaseController
{
    protected string $schedulePath = 'schedule';
    protected string $configurationPath = 'tools/googleSchedule';

    protected function active(): ?array
    {
        return (new UserServiceModel())->googleConnection((array) session('auth_user'));
    }
    public function index()
    {
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
        try {
            $service = $this->active();
            if ($service === null) return redirect()->to(site_url($this->configurationPath))->with('error', 'Cadastre o serviço Google Agenda.');
            session()->set('schedule_attempt_' . $service['id'] . '_' . sha1($service['email']), time());
            (new GoogleScheduleModel())->replaceSnapshot($service, (new GoogleCalendarClient())->fetch($service));
            return redirect()->to(site_url($this->schedulePath))->with('success', 'Agenda atualizada.');
        } catch (Throwable $exception) {
            return redirect()->to(site_url($this->schedulePath))->with('error', $exception instanceof \App\Libraries\GoogleCalendarException
                ? $exception->getMessage() : 'Não foi possível atualizar a agenda.');
        }
    }
    public function subject(int $id)
    {
        try {
            $service = $this->active();
            if ($service === null) return redirect()->to(site_url($this->configurationPath));
            $value = $this->request->getPost('subject_id');
            if (!is_string($value) || ($value !== '' && (!ctype_digit($value) || (int) $value < 1))) {
                return redirect()->to(site_url($this->schedulePath))->with('error', 'Selecione um assunto válido.');
            }
            if (!(new GoogleScheduleModel())->assignSubject($id, $value === '' ? null : (int) $value, $service)) {
                throw new \RuntimeException();
            }
            return redirect()->to(site_url($this->schedulePath))->with('success', 'Assunto da reunião atualizado.');
        } catch (PageNotFoundException $exception) { throw $exception; }
        catch (Throwable $exception) {
            return redirect()->to(site_url($this->schedulePath))->with('error', 'Não foi possível atualizar o assunto.');
        }
    }
}
