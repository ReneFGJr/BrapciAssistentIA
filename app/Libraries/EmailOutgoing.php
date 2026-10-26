<?php
namespace App\Libraries;

use InvalidArgumentException;
use RuntimeException;

class EmailOutgoing
{
    public function prepare(array $message, array $actor, string $action, string $reply = ''): array
    {
        $userEmail = $actor['email'] ?? '';
        if (!is_string($userEmail) || !filter_var($userEmail, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $userEmail)) throw new InvalidArgumentException('Seu perfil precisa ter um e-mail válido.');
        if (!in_array($action, ['reply', 'reply_all', 'forward'], true)) throw new InvalidArgumentException('Ação inválida.');
        $subject = preg_replace('/[\r\n]+/', ' ', $message['email_title'] ?: '(Sem assunto)');
        $quoted = "\n\n----- Mensagem original -----\nDe: " . $message['email_from'] . "\nPara: " . $message['email_to'] . "\nData: " . ($message['email_data'] ?? '') . " UTC\nAssunto: " . $subject . "\n\n" . $message['email_context'];
        if (!empty($message['has_attachments'])) $quoted .= "\n\n[Anexos da mensagem original não incluídos.]";
        if ($action === 'forward') return ['to' => $userEmail, 'replyTo' => $userEmail, 'subject' => 'Fwd: ' . $subject, 'body' => $quoted];
        if (trim($reply) === '' || mb_strlen($reply) > 50000) throw new InvalidArgumentException('Escreva uma resposta com até 50.000 caracteres.');
        if (!function_exists('imap_rfc822_parse_adrlist')) throw new RuntimeException('A extensão IMAP é necessária para identificar o remetente.');
        $targets = $this->addresses($message['email_reply_to'] ?? '');
        if ($targets === []) $targets = $this->addresses($message['email_from']);
        if ($targets === []) throw new InvalidArgumentException('O remetente não possui um endereço válido para resposta.');
        $cc = [];
        if ($action === 'reply_all') {
            $own = strtolower($userEmail);
            $targets = array_filter($targets, static fn($address) => strtolower($address) !== $own);
            foreach ($this->addresses($message['email_to'] ?? '') as $key => $address) {
                if ($key !== $own && !isset($targets[$key])) $targets[$key] = $address;
            }
            foreach ($this->addresses($message['email_cc'] ?? '') as $key => $address) {
                if ($key !== $own && !isset($targets[$key])) $cc[$key] = $address;
            }
            if ($targets === [] && $cc === []) throw new InvalidArgumentException('Não há outros destinatários válidos para responder.');
        }
        return ['to' => implode(', ', $targets), 'cc' => implode(', ', $cc), 'replyTo' => $userEmail, 'subject' => preg_match('/^Re:/i', $subject) ? $subject : 'Re: ' . $subject, 'body' => trim($reply) . $quoted];
    }

    private function addresses(string $header): array
    {
        $result = [];
        if (preg_match('/[\r\n]/', $header)) return [];
        foreach (imap_rfc822_parse_adrlist($header, '') ?: [] as $address) {
            $email = ($address->mailbox ?? '') . '@' . ($address->host ?? '');
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) $result[strtolower($email)] = $email;
        }
        return $result;
    }

    public function smtpConfiguration(array $account): \Config\Email
    {
        if ((int) ($account['status'] ?? 0) !== 1 || !filter_var($account['email_user'] ?? '', FILTER_VALIDATE_EMAIL) || !is_string($account['password'] ?? null) || $account['password'] === '') throw new InvalidArgumentException('Ative a conta e confira suas credenciais antes de enviar.');
        $host = trim((string) ($account['smtp_host'] ?? ''));
        if ($host === '') throw new InvalidArgumentException('Configure o Servidor SMTP em Gerenciar contas antes de enviar. O servidor IMAP é usado somente para receber e-mails.');
        if (!filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || strlen($host) > 253) throw new InvalidArgumentException('Confira o servidor SMTP na edição da conta.');
        $security = $account['smtp_security'] ?? 'tls';
        $port = filter_var($account['smtp_port'] ?? 587, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        if ($port === false || !in_array($security, ['tls', 'ssl', 'none'], true)) throw new InvalidArgumentException('Confira a porta e a segurança SMTP da conta.');
        $config = new \Config\Email();
        $config->protocol = 'smtp';
        $config->SMTPHost = ($security === 'ssl' ? 'ssl://' : '') . $host;
        $config->SMTPPort = $port;
        $config->SMTPCrypto = $security === 'tls' ? 'tls' : '';
        $config->SMTPUser = $account['email_user'];
        $config->SMTPPass = $account['password'];
        $config->fromEmail = $account['email_user'];
        $config->fromName = '';
        $config->SMTPTimeout = 15;
        $config->SMTPKeepAlive = false;
        $config->SMTPAuthMethod = 'login';
        $config->recipients = '';
        $config->mailType = 'text';
        $config->charset = 'UTF-8';
        $config->validate = true;
        return $config;
    }

    public function send(array $message, array $actor, string $action, string $reply, array $account): void
    {
        $mail = $this->prepare($message, array_replace($actor, ['email' => $action === 'forward' ? ($actor['email'] ?? '') : ($account['email_user'] ?? '')]), $action, $reply);
        $config = $this->smtpConfiguration($account);
        $sender = service('email', null, false);
        $sender->initialize($config);
        $sender->setFrom($config->fromEmail, $config->fromName);
        $sender->setReplyTo($account['email_user']);
        $sender->setTo($mail['to']);
        if (!empty($mail['cc'])) $sender->setCC($mail['cc']);
        $sender->setSubject($mail['subject']);
        $sender->setMessage($mail['body']);
        if (!$sender->send()) throw new RuntimeException('O servidor não confirmou o envio. Confira o SMTP na edição da conta e se a senha permite enviar e-mails.');
    }
}
