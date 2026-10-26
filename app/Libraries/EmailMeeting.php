<?php
namespace App\Libraries;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

class EmailMeeting
{
    public static function payload(array $message, array $values, string $reference): array
    {
        if (array_key_exists('start_date', $values)) {
            if (!is_string($values['start_date']) || !is_string($values['start_time'] ?? null)) throw new InvalidArgumentException('Informe a data no formato dd/mm/aaaa e o horário de início.');
            $input = $values['start_date'] . ' ' . $values['start_time'];
            $date = DateTimeImmutable::createFromFormat('!d/m/Y H:i', $input, new DateTimeZone('America/Sao_Paulo'));
            if (!$date || $date->format('d/m/Y H:i') !== $input) throw new InvalidArgumentException('Informe uma data válida no formato dd/mm/aaaa e o horário de início.');
            $values['start'] = $date->format('Y-m-d\TH:i');
        }
        if (array_key_exists('duration', $values)) {
            if (!is_string($values['duration']) || !in_array($values['duration'], ['15', '30', '60', '120', '180'], true)) throw new InvalidArgumentException('Selecione uma duração válida para a reunião.');
            $start = is_string($values['start'] ?? null) ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $values['start'], new DateTimeZone('America/Sao_Paulo')) : false;
            if (!$start || $start->format('Y-m-d\TH:i') !== $values['start']) throw new InvalidArgumentException('Informe uma data e horário de início válidos.');
            $values['end'] = $start->modify('+' . $values['duration'] . ' minutes')->format('Y-m-d\TH:i');
        }
        foreach (['title', 'start', 'end', 'location', 'guests'] as $field) {
            if (!is_string($values[$field] ?? null)) throw new InvalidArgumentException('Confira os campos da reunião.');
        }
        $title = trim($values['title']);
        if ($title === '' || mb_strlen($title) > 255 || mb_strlen($values['location']) > 1000) throw new InvalidArgumentException('Informe um título de até 255 caracteres e um local de até 1.000 caracteres.');
        $zone = new DateTimeZone('America/Sao_Paulo');
        $dates = [];
        foreach (['start', 'end'] as $field) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $values[$field], $zone);
            if (!$date || $date->format('Y-m-d\TH:i') !== $values[$field]) throw new InvalidArgumentException('Informe datas e horários válidos.');
            $dates[$field] = $date;
        }
        if ($dates['end'] <= $dates['start']) throw new InvalidArgumentException('O término deve ser posterior ao início.');
        if ($dates['start'] <= new DateTimeImmutable('now', $zone)) throw new InvalidArgumentException('Escolha um horário futuro para a reunião.');
        if (strlen($values['guests']) > 5000) throw new InvalidArgumentException('Informe até 50 convidados.');
        $guests = [];
        foreach (preg_split('/[,;\s]+/', trim($values['guests']), -1, PREG_SPLIT_NO_EMPTY) as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Informe e-mails válidos para os convidados, separados por vírgula.');
            $guests[strtolower($email)] = ['email' => $email];
        }
        if (count($guests) > 50) throw new InvalidArgumentException('Informe até 50 convidados.');
        $body = [
            'summary' => $title, 'location' => trim($values['location']),
            'description' => "Reunião agendada a partir de um e-mail.\nReferência: " . $reference
                . "\nAssunto: " . $message['email_title'] . "\nDe: " . $message['email_from']
                . "\nMessage-ID: " . ($message['message_id'] ?? '') . "\n\n" . mb_substr($message['email_context'], 0, 2000),
            'source' => ['title' => 'E-mail de origem', 'url' => $reference],
            'extendedProperties' => ['private' => ['email_message_id' => (string) $message['id'], 'email_account_id' => (string) $message['email_user_id']]],
            'start' => ['dateTime' => $dates['start']->format(DATE_RFC3339), 'timeZone' => $zone->getName()],
            'end' => ['dateTime' => $dates['end']->format(DATE_RFC3339), 'timeZone' => $zone->getName()],
        ];
        if ($guests !== []) $body['attendees'] = array_values($guests);
        if (($values['meet'] ?? '') === '1') $body['conferenceData'] = ['createRequest' => ['requestId' => bin2hex(random_bytes(16)), 'conferenceSolutionKey' => ['type' => 'hangoutsMeet']]];
        $body['description'] = htmlspecialchars($body['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return $body;
    }

    public static function safeUrl(?string $url, string $host): string
    {
        $hosts = $host === 'www.google.com' ? ['www.google.com', 'calendar.google.com'] : [$host];
        return is_string($url) && parse_url($url, PHP_URL_SCHEME) === 'https' && in_array(parse_url($url, PHP_URL_HOST), $hosts, true) ? $url : '';
    }
}
