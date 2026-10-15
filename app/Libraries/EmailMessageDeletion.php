<?php
namespace App\Libraries;

use RuntimeException;

class EmailMessageDeletion
{
    public function delete(array $account, array $message): bool
    {
        if ($account['protocol'] !== 'imap') throw new RuntimeException('A exclusão no servidor está disponível apenas para contas IMAP.');
        $capabilities = $this->command($account, 'CAPABILITY');
        if (!preg_match('/\b(UIDPLUS|IMAP4rev2)\b/i', $capabilities)) throw new RuntimeException('O servidor não suporta exclusão individual segura (UIDPLUS). A mensagem foi mantida.');
        $status = $this->command($account, 'STATUS INBOX (UIDVALIDITY UIDNEXT)');
        if (!preg_match('/UIDVALIDITY\s+(\d+)/i', $status, $match)) throw new RuntimeException('Não foi possível identificar a caixa de entrada.');
        $validity = $match[1];
        if (!preg_match('/UIDNEXT\s+(\d+)/i', $status, $match)) throw new RuntimeException('Não foi possível identificar os limites da caixa de entrada.');
        $nextUid = (int) $match[1];
        $target = null;
        $started = microtime(true);
        // Existing imports store a hash of the UID. Resolve it locally rather than
        // downloading SEARCH ALL, whose single response line may exceed cURL limits.
        for ($uid = $nextUid - 1; $uid >= 1; $uid--) {
            if (microtime(true) - $started > 10) throw new RuntimeException('A localização da mensagem excedeu o tempo limite. A cópia local foi mantida.');
            $key = hash('sha256', 'imap|' . $account['mail_user'] . '|' . $account['email_user'] . '|' . $validity . '|' . $uid);
            if (hash_equals($message['remote_key'], $key)) { $target = (string) $uid; break; }
        }
        if ($target === null) throw new RuntimeException('A mensagem não foi localizada na caixa IMAP atual. A cópia local foi mantida.');
        $search = $this->command($account, 'UID SEARCH UID ' . $target, $validity);
        if (!preg_match('/^\* SEARCH(?:[ \t]+([0-9 \t]*))?[ \t]*\r?$/mi', $search, $match)) throw new RuntimeException('O servidor retornou uma busca IMAP inesperada. A cópia local foi mantida.');
        $found = preg_split('/\s+/', trim($match[1] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        // A confirmed empty result means a previous deletion/move already removed it.
        // Treat retries as successful without issuing any mutation on the server.
        if ($found === []) return false;
        if ($found !== [$target]) throw new RuntimeException('O servidor retornou um identificador IMAP diferente do solicitado. A cópia local foi mantida.');
        $this->command($account, 'UID STORE ' . $target . ' +FLAGS.SILENT (\\Deleted)', $validity);
        // UID EXPUNGE removes only this UID, preserving other messages marked for deletion.
        $this->command($account, 'UID EXPUNGE ' . $target, $validity);
        return true;
    }

    protected function command(array $account, string $command, ?string $validity = null): string
    {
        if (!function_exists('curl_init')) throw new RuntimeException('Habilite a extensão cURL do PHP.');
        if (!preg_match('/^[a-zA-Z0-9.-]+$/D', $account['mail_user']) || !in_array($account['security'], ['ssl', 'tls', 'none'], true)) throw new RuntimeException('Configuração IMAP inválida.');
        $url = ($account['security'] === 'ssl' ? 'imaps' : 'imap') . '://' . $account['mail_user'] . ':' . (int) $account['mail_port_user'] . '/INBOX' . ($validity !== null ? ';UIDVALIDITY=' . $validity : '');
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_USERNAME => $account['email_user'], CURLOPT_PASSWORD => $account['password'], CURLOPT_CUSTOMREQUEST => $command, CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 25, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_USE_SSL => $account['security'] === 'none' ? CURLUSESSL_NONE : CURLUSESSL_ALL]);
        // On Windows, IMAP and cURL may otherwise use different trust stores.
        // OpenSSL-backed cURL supports the Windows native CA store since 8.3.0.
        if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA') && version_compare(curl_version()['version'], '8.3.0', '>=')) {
            curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
        }
        $caBundle = (string) env('emailImap.caBundle', '');
        if ($caBundle !== '') curl_setopt($curl, CURLOPT_CAINFO, $caBundle);
        try {
            $result = curl_exec($curl);
            if ($result === false) {
                $code = curl_errno($curl);
                $stage = explode(' ', $command)[0] === 'UID' ? implode(' ', array_slice(explode(' ', $command), 0, 2)) : explode(' ', $command)[0];
                // Never log credentials or the full server response.
                log_message('error', 'IMAP deletion failed at {stage}; cURL code {code}.', ['stage' => $stage, 'code' => $code]);
                $detail = match ($code) {
                    60, 77 => 'Falha ao validar o certificado IMAP. Configure emailImap.caBundle com a cadeia de certificados confiável do servidor.',
                    67 => 'O servidor IMAP recusou a autenticação. Confira a senha ou senha de aplicativo.',
                    6, 7 => 'Não foi possível conectar ao servidor IMAP. Confira o endereço e a porta.',
                    28 => 'O servidor IMAP excedeu o tempo de resposta.',
                    100 => 'A resposta IMAP excedeu o limite de tamanho aceito pelo cURL.',
                    default => 'O servidor IMAP não confirmou a operação. Verifique a conexão e as permissões da conta.',
                };
                throw new RuntimeException($detail . ' Etapa: ' . $stage . ' (código ' . $code . '). A cópia local foi mantida.');
            }
            return $result;
        } finally {
            curl_close($curl);
        }
    }
}
