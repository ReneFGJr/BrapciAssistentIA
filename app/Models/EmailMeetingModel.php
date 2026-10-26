<?php
namespace App\Models;

use CodeIgniter\Model;
use InvalidArgumentException;
use RuntimeException;
use App\Libraries\EmailMeeting;

class EmailMeetingModel extends Model
{
    protected $table = 'email_meetings';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'email_user_id', 'message_id', 'service_id', 'request_key', 'google_event_id', 'payload', 'status', 'event_url', 'meet_url', 'created_at'];

    public function pending(array $actor, array $message, array $service, string $key, array $payload): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $key)) throw new InvalidArgumentException('Reabra o formulário da reunião e tente novamente.');
        (new EmailUserRowModel($this->db))->findOwned((int) $message['email_user_id'], (int) $message['id'], $actor);
        if ((string) $service['user_id'] !== (string) $actor['id']) throw new InvalidArgumentException('Agenda inválida.');
        $existing = $this->where('user_id', (string) $actor['id'])->where('request_key', $key)->first();
        if ($existing !== null) {
            if ((int) $existing['message_id'] !== (int) $message['id'] || (int) $existing['service_id'] !== (int) $service['id']) throw new InvalidArgumentException('Solicitação de reunião inválida.');
            $original = json_decode($existing['payload'], true, 512, JSON_THROW_ON_ERROR);
            unset($original['id']);
            if (isset($original['conferenceData'], $payload['conferenceData'])) {
                unset($original['conferenceData']['createRequest']['requestId'], $payload['conferenceData']['createRequest']['requestId']);
            }
            if ($original !== $payload) throw new InvalidArgumentException('Esta solicitação já foi iniciada com outros dados. Mantenha os dados originais para recuperar a mesma reunião.');
            return $existing;
        }
        $payload['id'] = hash('sha256', $actor['id'] . ':' . $service['id'] . ':' . $key);
        $id = $this->insert(['user_id' => (string) $actor['id'], 'email_user_id' => $message['email_user_id'], 'message_id' => $message['id'],
            'service_id' => $service['id'], 'request_key' => $key, 'google_event_id' => $payload['id'], 'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'status' => 'pending', 'created_at' => gmdate('Y-m-d H:i:s')]);
        if (!$id) throw new RuntimeException('Não foi possível preparar o registro da reunião.');
        return $this->find($id);
    }

    public function confirm(array $actor, array $meeting, array $event): void
    {
        $meet = $event['hangoutLink'] ?? '';
        foreach ($event['conferenceData']['entryPoints'] ?? [] as $entry) {
            if (($entry['entryPointType'] ?? '') === 'video') $meet = $entry['uri'] ?? '';
        }
        if (!$this->where('user_id', (string) $actor['id'])->update($meeting['id'], ['status' => 'confirmed',
            'event_url' => EmailMeeting::safeUrl($event['htmlLink'] ?? '', 'www.google.com'),
            'meet_url' => EmailMeeting::safeUrl($meet, 'meet.google.com')])) throw new RuntimeException('O Google criou a reunião, mas não foi possível atualizar o vínculo. Tente novamente neste formulário para recuperar o registro.');
    }

    public function forUser(array $actor): array
    {
        if (empty($actor['id'])) return [];
        return $this->where('user_id', (string) $actor['id'])->where('status', 'confirmed')->orderBy('id', 'DESC')->findAll();
    }
}
