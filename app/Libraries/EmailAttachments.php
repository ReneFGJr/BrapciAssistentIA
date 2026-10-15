<?php
namespace App\Libraries;

use RuntimeException;

class EmailAttachments
{
    public static function parts(object $part, string $section = ''): array
    {
        $params = [];
        foreach ([$part->parameters ?? [], $part->dparameters ?? []] as $collection) {
            // IMAP implementations can return the parameter list as stdClass.
            $parameters = is_object($collection) && isset($collection->attribute) ? [$collection] : (array) $collection;
            foreach ($parameters as $parameter) {
                $parameter = (object) $parameter;
                if (!is_string($parameter->attribute ?? null) || !is_string($parameter->value ?? null)) continue;
                $params[strtolower($parameter->attribute)] = $parameter->value;
            }
        }
        $filename = $params['filename'] ?? $params['name'] ?? '';
        foreach (['filename', 'name'] as $key) {
            $extended = $params[$key . '*'] ?? '';
            if ($extended === '') {
                for ($i = 0; isset($params[$key . '*' . $i . '*']); $i++) $extended .= $params[$key . '*' . $i . '*'];
            }
            if ($extended !== '') {
                $pieces = explode("'", $extended, 3);
                $filename = rawurldecode($pieces[2] ?? $extended);
                if (count($pieces) === 3 && $pieces[0] !== '') {
                    try { $filename = mb_convert_encoding($filename, 'UTF-8', $pieces[0]); } catch (\ValueError $e) {}
                }
                break;
            }
        }
        if ($filename !== '' || strtolower($part->disposition ?? '') === 'attachment' || ((int) $part->type !== 0 && strtolower($part->disposition ?? '') === 'inline')) {
            $filename = EmailInbox::decodeHeader($filename ?: 'anexo-' . ($section ?: '1'));
            $filename = preg_replace('/[\x00-\x1f\x7f\/\\\\]/u', '_', $filename);
            return [['section' => $section ?: '1', 'name' => mb_substr($filename, 0, 200), 'size' => (int) ($part->bytes ?? 0), 'encoding' => (int) ($part->encoding ?? 0)]];
        }
        $result = [];
        foreach ($part->parts ?? [] as $index => $child) $result = array_merge($result, self::parts($child, $section === '' ? (string) ($index + 1) : $section . '.' . ($index + 1)));
        return $result;
    }

    public function fetch(array $account, array $message, ?string $section = null): array
    {
        if (!function_exists('imap_open')) throw new RuntimeException('Extensão IMAP indisponível.');
        if (!preg_match('/^[a-zA-Z0-9.-]+$/D', $account['mail_user']) || !in_array($account['protocol'], ['imap', 'pop3'], true) || !in_array($account['security'], ['ssl', 'tls', 'none'], true)) throw new RuntimeException('Configuração de conexão inválida.');
        $security = ['ssl' => '/ssl/validate-cert', 'tls' => '/tls/validate-cert', 'none' => '/notls'][$account['security']];
        $mailbox = '{' . $account['mail_user'] . ':' . (int) $account['mail_port_user'] . '/' . $account['protocol'] . '/norsh' . $security . '}INBOX';
        imap_timeout(IMAP_OPENTIMEOUT, 10); imap_timeout(IMAP_READTIMEOUT, 15);
        $stream = @imap_open($mailbox, $account['email_user'], $account['password'], $account['protocol'] === 'imap' ? OP_READONLY : 0, 1);
        if (!$stream) { imap_errors(); imap_alerts(); throw new RuntimeException('Não foi possível consultar os anexos no servidor.'); }
        try {
            $number = 0;
            $started = microtime(true);
            $prefix = $account['protocol'] . '|' . $account['mail_user'] . '|' . $account['email_user'] . '|';
            if ($account['protocol'] === 'imap') {
                $status = imap_status($stream, $mailbox, SA_UIDVALIDITY | SA_UIDNEXT);
                if (!$status || empty($status->uidvalidity) || empty($status->uidnext)) throw new RuntimeException('Não foi possível identificar a caixa.');
                for ($uid = (int) $status->uidnext - 1; $uid >= 1; $uid--) {
                    if (microtime(true) - $started > 10) throw new RuntimeException('Tempo limite ao localizar mensagem.');
                    if (hash_equals($message['remote_key'], hash('sha256', $prefix . $status->uidvalidity . '|' . $uid))) { $number = imap_msgno($stream, $uid); break; }
                }
            } else {
                for ($i = imap_num_msg($stream); $i >= 1; $i--) {
                    if (microtime(true) - $started > 15) throw new RuntimeException('Tempo limite ao localizar mensagem.');
                    $header = imap_fetchheader($stream, $i);
                    if ($header !== false && hash_equals($message['remote_key'], hash('sha256', $prefix . 'pop3|' . $header))) { $number = $i; break; }
                }
            }
            if (!$number) throw new RuntimeException('A mensagem não está mais disponível nesta caixa no servidor.');
            $structure = imap_fetchstructure($stream, $number);
            if (!$structure) throw new RuntimeException('Não foi possível consultar a estrutura da mensagem.');
            $parts = self::parts($structure);
            if ($section === null) return array_map(static fn($part) => array_diff_key($part, ['encoding' => true]), $parts);
            foreach ($parts as $part) {
                if ($part['section'] !== $section) continue;
                if ($part['size'] > 20 * 1024 * 1024) throw new RuntimeException('O anexo excede o limite de download de 20 MB.');
                $raw = imap_fetchbody($stream, $number, $section, FT_PEEK);
                if ($raw === false) throw new RuntimeException('Não foi possível baixar o anexo.');
                $content = match ($part['encoding']) { 3 => base64_decode($raw, true), 4 => quoted_printable_decode($raw), default => $raw };
                if ($content === false) throw new RuntimeException('O conteúdo do anexo é inválido.');
                return ['name' => $part['name'], 'content' => $content];
            }
            throw new RuntimeException('Anexo não encontrado.');
        } finally { imap_close($stream); imap_errors(); imap_alerts(); }
    }
}
