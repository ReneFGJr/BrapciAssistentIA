<?php
namespace App\Libraries;
use DateTimeImmutable;
use DateTimeZone;
class GoogleCalendarClient
{
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
                    ['headers' => ['X-Goog-Api-Key' => $credentials['api_key'], 'Accept' => 'application/json'],
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
                    ? 'O Google não liberou esta agenda. Confira a API key e o e-mail. Agendas privadas exigem OAuth; API key permite consultar agendas públicas.'
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
            'starts_at' => $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'ends_at' => $end->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'all_day' => $allDay ? 1 : 0, 'timezone' => $timezone,
            'status' => ($event['status'] ?? '') === 'tentative' ? 'tentative' : 'confirmed',
        ];
    }
}
