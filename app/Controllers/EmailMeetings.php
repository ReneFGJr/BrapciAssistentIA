<?php
namespace App\Controllers;

use App\Libraries\EmailMeeting;
use App\Libraries\GoogleCalendarClient;
use App\Libraries\GoogleCalendarException;
use App\Libraries\GoogleCalendarOAuth;
use App\Models\EmailMeetingModel;
use App\Models\EmailUserRowModel;
use App\Models\GoogleScheduleModel;
use App\Models\PrivateGoogleServiceModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use InvalidArgumentException;
use Throwable;

class EmailMeetings extends BaseController
{
    public function form(int $accountId, int $messageId): string
    {
        $actor = (array) session('auth_user');
        $message = (new EmailUserRowModel())->findOwned($accountId, $messageId, $actor);
        $this->response->setHeader('Cache-Control', 'no-store');
        return view('main', ['content' => view('email/meeting', ['message' => $message,
            'calendar' => (new PrivateGoogleServiceModel())->summary($actor), 'requestKey' => bin2hex(random_bytes(16))])]);
    }

    public function create(int $accountId, int $messageId)
    {
        $this->response->setHeader('Cache-Control', 'no-store');
        try {
            $actor = (array) session('auth_user');
            $message = (new EmailUserRowModel())->findOwned($accountId, $messageId, $actor);
            $service = (new GoogleCalendarOAuth())->access($actor);
            if ($service === null || empty($service['can_write'])) throw new GoogleCalendarException('Conecte novamente o Google Agenda particular e autorize a criação de eventos.');
            $values = [];
            foreach (['title', 'start', 'end', 'location', 'guests', 'meet'] as $field) $values[$field] = $this->request->getPost($field) ?? '';
            $key = $this->request->getPost('request_key');
            $payload = EmailMeeting::payload($message, $values, site_url('email/account') . '?message=' . $messageId);
            $model = new EmailMeetingModel();
            $meeting = $model->pending($actor, $message, $service, is_string($key) ? $key : '', $payload);
            $payload = json_decode($meeting['payload'], true, 512, JSON_THROW_ON_ERROR);
            $event = (new GoogleCalendarClient())->createMeeting($service, $payload);
            $model->confirm($actor, $meeting, $event);
            $warning = '';
            try { (new GoogleScheduleModel())->storeCreated($service, GoogleCalendarClient::normalize($event, 'America/Sao_Paulo')); }
            catch (Throwable $e) { $warning = ' Atualize a agenda para carregar a cópia local.'; }
            $meetRequested = isset($payload['conferenceData']);
            $meetFailure = ($event['conferenceData']['createRequest']['status']['statusCode'] ?? '') === 'failure';
            $notice = $meetRequested ? ($meetFailure ? ' O Google não conseguiu gerar o Meet; abra o evento para adicionar a videoconferência.' : ' Se o link Meet ainda estiver sendo gerado, consulte o evento no Google Agenda.') : '';
            return $this->response->setJSON(['message' => 'Reunião confirmada no Google Agenda e vinculada ao e-mail.' . $notice . $warning,
                'event_url' => EmailMeeting::safeUrl($event['htmlLink'] ?? '', 'www.google.com'),
                'email_url' => site_url('email/account') . '?message=' . $messageId, 'csrf' => csrf_hash()]);
        } catch (PageNotFoundException $e) { throw $e;
        } catch (GoogleCalendarException | InvalidArgumentException $e) { $error = $e->getMessage();
        } catch (Throwable $e) {
            log_message('error', 'Email meeting failed: account {account}, message {message}, type {type}.', ['account' => $accountId, 'message' => $messageId, 'type' => get_class($e)]);
            $error = 'Não foi possível confirmar o registro da reunião. Tente novamente neste formulário para recuperar a mesma solicitação sem duplicá-la.';
        }
        return $this->response->setStatusCode(422)->setJSON(['error' => $error, 'csrf' => csrf_hash()]);
    }
}
