<?php
namespace App\Libraries;

use App\Models\EmailUserRowModel;
use RuntimeException;

class EmailInbox
{
    public function sync(array $account, array $actor, EmailUserRowModel $messages): int
    {
        if (!function_exists('imap_open')) throw new RuntimeException('Habilite a extensão IMAP do PHP no servidor.');
        if (!preg_match('/^[a-zA-Z0-9.-]+$/D', $account['mail_user']) || !in_array($account['protocol'], ['imap', 'pop3'], true) || !in_array($account['security'], ['ssl', 'tls', 'none'], true)) throw new RuntimeException('Configuração de servidor inválida.');
        $security = ['ssl' => '/ssl/validate-cert', 'tls' => '/tls/validate-cert', 'none' => '/notls'][$account['security']];
        $mailbox = '{' . $account['mail_user'] . ':' . (int) $account['mail_port_user'] . '/' . $account['protocol'] . '/norsh' . $security . '}INBOX';
        imap_timeout(IMAP_OPENTIMEOUT, 10);
        imap_timeout(IMAP_READTIMEOUT, 10);
        imap_timeout(IMAP_WRITETIMEOUT, 10);
        $stream = @imap_open($mailbox, $account['email_user'], $account['password'], $account['protocol'] === 'imap' ? OP_READONLY : 0, 1);
        if ($stream === false) {
            imap_errors(); imap_alerts();
            throw new RuntimeException('Não foi possível conectar. Confira servidor, porta, segurança e senha de aplicativo.');
        }
        $count = 0;
        $started = microtime(true);
        try {
            $validity = 'pop3';
            if ($account['protocol'] === 'imap') {
                $status = imap_status($stream, $mailbox, SA_UIDVALIDITY);
                if (!$status || empty($status->uidvalidity)) throw new RuntimeException('Não foi possível identificar a caixa de entrada.');
                $validity = (string) $status->uidvalidity;
            }
            // Newest first. Repeated clicks continue importing older, missing messages.
            for ($number = imap_num_msg($stream); $number >= 1 && $count < 50 && microtime(true) - $started < 20; $number--) {
                $header = imap_fetchheader($stream, $number);
                $overview = imap_fetch_overview($stream, (string) $number, 0);
                if ($header === false || !$overview) throw new RuntimeException('Falha ao consultar uma mensagem. Tente atualizar novamente.');
                $info = $overview[0];
                $key = hash('sha256', $account['protocol'] . '|' . $account['mail_user'] . '|' . $account['email_user'] . '|' . $validity . '|' . ($account['protocol'] === 'imap' ? imap_uid($stream, $number) : $header));
                if ($messages->known((int) $account['id'], $key, $actor)) continue;
                // Do not fetch oversized bodies; leave them for the original mail client.
                if (($info->size ?? 0) > 10 * 1024 * 1024) continue;
                $structure = imap_fetchstructure($stream, $number);
                if (!$structure) throw new RuntimeException('Falha ao consultar o conteúdo da mensagem.');
                $body = $this->body($stream, $number, $structure);
                $parsedDate = isset($info->date) ? strtotime($info->date) : false;
                $messages->import((int) $account['id'], [
                    'remote_key' => $key, 'message_id' => $info->message_id ?? null,
                    'email_from' => self::decodeHeader($info->from ?? ''), 'email_to' => self::decodeHeader($info->to ?? ''),
                    'email_title' => self::decodeHeader($info->subject ?? '(Sem assunto)'), 'email_context' => $body,
                    'email_data' => $parsedDate === false ? null : gmdate('Y-m-d H:i:s', $parsedDate),
                    'email_status' => !empty($info->flagged) ? 2 : (!empty($info->seen) ? 1 : 0),
                    'size_bytes' => (int) ($info->size ?? 0), 'has_attachments' => $this->hasAttachments($structure) ? 1 : 0,
                ], $actor);
                $count++;
            }
        } finally {
            imap_close($stream);
            imap_errors(); imap_alerts();
        }
        return $count;
    }

    public static function decodeHeader(string $value): string
    {
        $result = '';
        foreach (imap_mime_header_decode($value) ?: [] as $part) {
            $charset = in_array(strtolower($part->charset), ['default', 'us-ascii'], true) ? 'UTF-8' : $part->charset;
            try { $result .= mb_convert_encoding($part->text, 'UTF-8', $charset); }
            catch (\ValueError $e) { $result .= mb_convert_encoding($part->text, 'UTF-8', 'UTF-8'); }
        }
        return $result;
    }

    private function hasAttachments(object $part): bool
    {
        if (EmailAttachments::parts($part) !== []) return true;
        if (in_array(strtolower($part->disposition ?? ''), ['attachment', 'inline'], true) && (int) $part->type !== 0) return true;
        foreach ($part->parts ?? [] as $child) if ($this->hasAttachments($child)) return true;
        return false;
    }

    private function body($stream, int $number, object $part, string $section = ''): string
    {
        if (strtolower($part->disposition ?? '') === 'attachment') return '';
        if ((int) $part->type === 1) {
            $texts = [];
            foreach ($part->parts ?? [] as $index => $child) {
                $text = $this->body($stream, $number, $child, $section === '' ? (string) ($index + 1) : $section . '.' . ($index + 1));
                if ($text !== '') $texts[] = $text;
                if (strtoupper($part->subtype ?? '') === 'ALTERNATIVE' && $text !== '') return $text;
            }
            return implode("\n\n", $texts);
        }
        if ((int) $part->type !== 0) return '';
        $raw = $section === '' ? imap_body($stream, $number, FT_PEEK) : imap_fetchbody($stream, $number, $section, FT_PEEK);
        if ($raw === false) throw new RuntimeException('Falha ao baixar conteúdo da mensagem.');
        $raw = match ((int) $part->encoding) { 3 => base64_decode($raw) ?: '', 4 => quoted_printable_decode($raw), default => $raw };
        $charset = 'UTF-8';
        foreach ($part->parameters ?? [] as $parameter) if (strtolower($parameter->attribute) === 'charset') $charset = $parameter->value;
        try { $raw = mb_convert_encoding($raw, 'UTF-8', $charset); }
        catch (\ValueError $e) { $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-8'); }
        if (strtoupper($part->subtype ?? '') === 'HTML') {
            $raw = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', '', $raw);
            $raw = preg_replace('~<(br|/p|/div)\b[^>]*>~i', "\n", $raw);
            $raw = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return trim($raw);
    }
}
