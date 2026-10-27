<?php
namespace App\Libraries;
use DateTimeImmutable;
use DateTimeZone;
class GoogleCalendarClient
{
    public function createMeeting(array $credentials, array $payload): array
    {
        if (empty($credentials['access_token']) || empty($credentials['can_write'])) throw new GoogleCalendarException('Conecte novamente o Google Agenda particular e autorize a criação de eventos. API key ou permissão somente de leitura não permite agendar reuniões.');
        $endpoint = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($credentials['email']) . '/events';
        $options = ['headers' => ['Authorization' => 'Bearer ' . $credentials['access_token'], 'Accept' => 'application/json'],
            'connect_timeout' => 5, 'timeout' => 20, 'http_errors' => false, 'allow_redirects' => false];
        try {
            // Reuse the same event ID on retry, including after a network timeout.
            $client = service('curlrequest');
            $response = $client->get($endpoint . '/' . rawurlencode($payload['id']), $options);
            if ($response->getStatusCode() === 404) {
                $response = $client->post($endpoint, $options + ['json' => $payload,
                    'query' => ['conferenceDataVersion' => 1, 'sendUpdates' => empty($payload['attendees']) ? 'none' : 'all']]);
                if ($response->getStatusCode() === 409) $response = $client->get($endpoint . '/' . rawurlencode($payload['id']), $options);
            }
            $code = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);
        } catch (\Throwable $e) {
            throw new GoogleCalendarException('Não foi possível confirmar a criação no Google. Tente novamente neste formulário para consultar a mesma reunião sem duplicá-la.');
        }
        if (in_array($code, [401, 403], true)) throw new GoogleCalendarException('O Google recusou a criação da reunião. Reconecte a agenda particular com permissão para criar eventos e confira se a conta permite usar Meet.');
        if (!in_array($code, [200, 201], true) || !is_array($body) || ($body['id'] ?? '') !== $payload['id'] || ($body['status'] ?? '') === 'cancelled') throw new GoogleCalendarException('O Google não confirmou a reunião. Confira os dados e tente novamente neste formulário.');
        if (($body['extendedProperties']['private']['email_message_id'] ?? '') !== $payload['extendedProperties']['private']['email_message_id']) throw new GoogleCalendarException('A referência da reunião retornada pelo Google é inválida.');
        return $body;
    }

    public function fetch(array $credentials): array
    {
        $events = [];
        $token = null;
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        for ($page = 0; $page < 20; $page++) {
            $query = [
                'timeMin' => $now->format(DATE_RFC3339),
                'timeMax' => $now->modify('+90 days')->format(DATE_RFC3339),
                'singleEvents' => 'true', 'orderBy' => 'startTime', 'maxResults' => 250,
            ];
            if ($token !== null) $query['pageToken'] = $token;
            try {
                $response = service('curlrequest')->get(
                    'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($credentials['email']) . '/events',
                    ['headers' => isset($credentials['access_token'])
                        ? ['Authorization' => 'Bearer ' . $credentials['access_token'], 'Accept' => 'application/json']
                        : ['X-Goog-Api-Key' => $credentials['api_key'], 'Accept' => 'application/json'],
                        'query' => $query, 'connect_timeout' => 5, 'timeout' => 15,
                        'http_errors' => false, 'allow_redirects' => false]
                );
                $code = $response->getStatusCode();
                $body = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $exception) {
                throw new GoogleCalendarException('Não foi possível consultar o Google Agenda. Tente novamente.');
            }
            if ($code !== 200) {
                throw new GoogleCalendarException(in_array($code, [401, 403, 404], true)
                    ? (isset($credentials['access_token']) ? 'O Google não liberou a agenda particular. Conecte novamente e autorize a leitura dos eventos.' : 'O Google não liberou esta agenda. Confira a API key e o e-mail. Agendas privadas exigem OAuth; API key permite consultar agendas públicas.')
                    : 'O Google Agenda está indisponível ou atingiu o limite de consultas. Tente novamente.');
            }
            if (!is_array($body) || !is_array($body['items'] ?? null)) {
                throw new GoogleCalendarException('Resposta inválida do Google Agenda.');
            }
            if (($body['accessRole'] ?? '') === 'freeBusyReader') {
                throw new GoogleCalendarException('A agenda permite consultar apenas horários de disponibilidade. Título e local exigem acesso aos detalhes; para uma agenda privada, configure autorização OAuth.');
            }
            $timezone = $body['timeZone'] ?? 'UTC';
            foreach ($body['items'] as $item) {
                if (($item['status'] ?? '') === 'cancelled') continue;
                $events[] = self::normalize($item, $timezone);
            }
            $token = $body['nextPageToken'] ?? null;
            if (!$token) return $events;
        }
        throw new GoogleCalendarException('A agenda excedeu o limite de eventos desta consulta. Os dados anteriores foram mantidos.');
    }
    public static function normalize(array $event, string $timezone): array
    {
        $allDay = isset($event['start']['date']);
        $zone = new DateTimeZone($timezone);
        $start = new DateTimeImmutable($event['start'][$allDay ? 'date' : 'dateTime'], $zone);
        $end = new DateTimeImmutable($event['end'][$allDay ? 'date' : 'dateTime'], $zone);
        if (empty($event['id']) || strlen($event['id']) > 255 || $end <= $start) {
            throw new GoogleCalendarException('Evento inválido recebido do Google.');
        }
        return [
            'google_event_id' => $event['id'],
            'title' => mb_substr(trim((string) ($event['summary'] ?? '')) ?: 'Sem título', 0, 255),
            'description' => mb_substr((string) ($event['description'] ?? ''), 0, 10000),
            'location' => mb_substr((string) ($event['location'] ?? ''), 0, 10000),
            'meeting_url' => self::meetingUrl($event),
            'google_url' => \App\Libraries\EmailMeeting::safeUrl($event['htmlLink'] ?? '', 'www.google.com'),
            'starts_at' => $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'ends_at' => $end->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'all_day' => $allDay ? 1 : 0, 'timezone' => $timezone,
            'status' => ($event['status'] ?? '') === 'tentative' ? 'tentative' : 'confirmed',
        ];
    }

    public static function meetingUrl(array $event): string
    {
        $candidates = [];
        foreach ($event['conferenceData']['entryPoints'] ?? [] as $entry) {
            if (($entry['entryPointType'] ?? '') === 'video') $candidates[] = $entry['uri'] ?? '';
        }
        $candidates[] = $event['hangoutLink'] ?? '';
        $candidates[] = $event['meeting_url'] ?? '';
        $candidates[] = $event['location'] ?? '';
        $description = html_entity_decode((string) ($event['description'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('~https://meet\.google\.com/[a-z0-9-]+~i', $description, $match)) $candidates[] = $match[0];
        foreach ($candidates as $url) {
            if (!is_string($url)) continue;
            $url = trim($url);
            if (strlen($url) <= 2048 && filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['https', 'http'], true)) return $url;
        }
        return '';
    }
}
