<?php
namespace App\Controllers;
use App\Models\UserServiceModel;
use App\Libraries\GoogleCalendarClient;
use App\Libraries\GoogleCalendarException;
use InvalidArgumentException;
use Throwable;
class GoogleSchedule extends BaseController
{
    public function index(): string
    {
        $configuration = null;
        $registeredServices = [];
        try {
            $model = new UserServiceModel();
            $registeredServices = $model->registeredFor((array) session('auth_user'));
            $configuration = $model->googleSummary((array) session('auth_user'));
        } catch (Throwable $exception) {
            log_message('error', 'Falha ao ler configuração Google Agenda.');
            session()->setFlashdata('error', 'Não foi possível carregar a configuração. Verifique a chave de criptografia do sistema.');
        }
        $this->response->setHeader('Cache-Control', 'no-store');
        return view('main', ['content' => view('tools/google_schedule', ['configuration' => $configuration, 'registeredServices' => $registeredServices])]);
    }
    public function delete(int $id)
    {
        try {
            if (!(new UserServiceModel())->deleteFor($id, (array) session('auth_user'))) {
                throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Serviço não encontrado.');
            }
            return redirect()->to('/tools/googleSchedule')->with('success', 'Serviço excluído.');
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            log_message('error', 'Falha ao excluir serviço do usuário.');
            return redirect()->to('/tools/googleSchedule')->with('error', 'Não foi possível excluir o serviço.');
        }
    }

    public function save()
    {
        $email = $this->request->getPost('email');
        $apiKey = $this->request->getPost('api_key');
        try {
            if (!is_string($email) || !is_string($apiKey)) {
                throw new InvalidArgumentException('Confira o e-mail e a API key.');
            }
            (new UserServiceModel())->saveGoogle((array) session('auth_user'), $email, $apiKey);
            return redirect()->to('/tools/googleSchedule')->with('success', 'Configuração do Google Agenda salva.');
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            log_message('error', 'Falha ao salvar configuração Google Agenda.');
            $error = 'Não foi possível salvar. Verifique a configuração de criptografia do sistema e tente novamente.';
        }
        // Do not put the API key in flash data or repopulate it in HTML.
        return redirect()->to('/tools/googleSchedule')->with('error', $error)
            ->with('google_schedule_email', is_string($email) ? mb_substr(trim($email), 0, 254) : '');
    }

    public function validateCredentials()
    {
        $this->response->setHeader('Cache-Control', 'private, no-store, max-age=0');
        try {
            $connection = (new UserServiceModel())->googleConnection((array) session('auth_user'));
            if ($connection === null) {
                return redirect()->to(site_url('tools/googleSchedule'))
                    ->with('error', 'Salve o e-mail e a API key antes de revalidar as credenciais.');
            }
            // A real read-only request validates the API key and access to this calendar.
            $events = (new GoogleCalendarClient())->fetch($connection);
            return redirect()->to(site_url('tools/googleSchedule'))->with(
                'success',
                'Credenciais revalidadas no Google com sucesso. ' . count($events) . ' evento(s) acessível(is).'
            );
        } catch (Throwable $exception) {
            log_message('notice', 'Falha ao revalidar Google Agenda: {type}', ['type' => $exception::class]);
            return redirect()->to(site_url('tools/googleSchedule'))->with(
                'error',
                $exception instanceof GoogleCalendarException
                    ? $exception->getMessage()
                    : 'Não foi possível revalidar as credenciais do Google Agenda.'
            );
        }
    }
}
