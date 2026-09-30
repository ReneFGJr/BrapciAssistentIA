<?php
namespace App\Controllers;

use App\Models\PrivateGoogleServiceModel;
use App\Libraries\GoogleCalendarOAuth;
use InvalidArgumentException;
use Throwable;

class UserGoogleSchedule extends BaseController
{
    private const PATH = 'tools/usergoogleSchedule';

    public function index(): string
    {
        $this->response->setHeader('Cache-Control', 'no-store')->setHeader('Referrer-Policy', 'no-referrer');
        $configuration = null;
        try {
            $configuration = (new PrivateGoogleServiceModel())->summary((array) session('auth_user'));
        } catch (Throwable $exception) {
            session()->setFlashdata('error', 'Não foi possível ler o serviço. Verifique a configuração de criptografia.');
        }
        return view('main', ['content' => view('tools/user_google_schedule', [
            'configuration' => $configuration, 'redirectUri' => GoogleCalendarOAuth::redirectUri(),
            'validRedirect' => GoogleCalendarOAuth::validRedirect(GoogleCalendarOAuth::redirectUri()),
        ])]);
    }

    public function save()
    {
        try {
            $values = [];
            foreach (['email', 'client_id', 'client_secret'] as $field) {
                $value = $this->request->getPost($field);
                if (!is_string($value)) throw new InvalidArgumentException('Confira os campos da configuração.');
                $values[] = $value;
            }
            (new PrivateGoogleServiceModel())->saveConfiguration((array) session('auth_user'), ...$values);
            session()->remove('private_calendar_oauth');
            return redirect()->to(site_url(self::PATH))->with('success', 'Serviço usergoogleSchedule salvo. Use Conectar com Google para autorizar a agenda.');
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            $error = 'Não foi possível salvar o serviço.';
        }
        // Never flash a submitted client secret.
        return redirect()->to(site_url(self::PATH))->with('error', $error);
    }

    public function connect()
    {
        try {
            $actor = (array) session('auth_user');
            $connection = (new PrivateGoogleServiceModel())->connection($actor);
            if ($connection === null) throw new InvalidArgumentException('Salve as credenciais primeiro.');
            $uri = GoogleCalendarOAuth::redirectUri();
            if (!GoogleCalendarOAuth::validRedirect($uri)) {
                throw new InvalidArgumentException('Para OAuth, acesse o sistema por localhost ou um domínio HTTPS válido e configure app.baseURL com esse endereço.');
            }
            $pending = ['state' => bin2hex(random_bytes(32)), 'verifier' => bin2hex(random_bytes(32)),
                'owner' => (string) $actor['id'], 'version' => $connection['version'],
                'expires_at' => time() + 600, 'redirect_uri' => $uri];
            session()->set('private_calendar_oauth', $pending);
            return redirect()->to(GoogleCalendarOAuth::authorizationUrl($connection, $pending));
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            $error = 'Não foi possível iniciar a conexão com Google.';
        }
        return redirect()->to(site_url(self::PATH))->with('error', $error);
    }

    public function callback()
    {
        $pending = session()->get('private_calendar_oauth');
        session()->remove('private_calendar_oauth');
        try {
            $actor = (array) session('auth_user');
            $model = new PrivateGoogleServiceModel();
            $connection = $model->connection($actor);
            if ($connection === null || !GoogleCalendarOAuth::validState(is_array($pending) ? $pending : null, $this->request->getGet('state'), $actor, $connection)) {
                throw new InvalidArgumentException('A autorização expirou ou é inválida. Inicie a conexão novamente.');
            }
            if ($this->request->getGet('error') !== null) throw new InvalidArgumentException('A autorização do Google não foi concedida.');
            $code = $this->request->getGet('code');
            if (!is_string($code) || $code === '') throw new InvalidArgumentException('O Google não enviou a autorização.');
            $tokens = (new GoogleCalendarOAuth())->tokens([
                'grant_type' => 'authorization_code', 'code' => $code, 'code_verifier' => $pending['verifier'],
                'redirect_uri' => $pending['redirect_uri'], 'client_id' => $connection['client_id'],
                'client_secret' => $connection['client_secret'],
            ]);
            $model->saveTokens($connection, $tokens);
            // Cached events may belong to a previously authorized Google account.
            db_connect()->table('service_google_schedule')->where('service_id', $connection['id'])
                ->where('user_id', $connection['user_id'])->delete();
            return redirect()->to(site_url('userSchedule'))->setHeader('Cache-Control', 'no-store')
                ->setHeader('Referrer-Policy', 'no-referrer')->with('success', 'Agenda particular conectada.');
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        } catch (Throwable $exception) {
            $error = 'Não foi possível concluir a autorização. Confira as credenciais e tente conectar novamente.';
        }
        return redirect()->to(site_url(self::PATH))->setHeader('Cache-Control', 'no-store')
            ->setHeader('Referrer-Policy', 'no-referrer')->with('error', $error);
    }

    public function delete()
    {
        try {
            (new PrivateGoogleServiceModel())->deleteFor((array) session('auth_user'));
            session()->remove('private_calendar_oauth');
            return redirect()->to(site_url(self::PATH))->with('success', 'Serviço particular e dados locais da agenda excluídos.');
        } catch (Throwable $exception) {
            return redirect()->to(site_url(self::PATH))->with('error', 'Não foi possível excluir o serviço.');
        }
    }
}
