<?php
require __DIR__ . '/email_inbox.php';
$outgoing = new App\Libraries\EmailOutgoing();
$actor['email'] = 'owner@example.test';
$message['email_from'] = 'Remetente <sender@example.test>';
$message['has_attachments'] = 1;
$forward = $outgoing->prepare($message, $actor, 'forward');
verifyService($forward['to'] === 'owner@example.test' && str_contains($forward['body'], $message['email_context']), 'Forward only to authenticated profile');
verifyService(str_contains($forward['body'], 'não incluídos'), 'Attachment omission disclosed');
$reply = $outgoing->prepare($message, $actor, 'reply', 'Minha resposta');
verifyService($reply['to'] === 'sender@example.test' && $reply['replyTo'] === $actor['email'] && str_starts_with($reply['body'], 'Minha resposta'), 'Reply recipient, reply-to and content');
foreach ([fn() => $outgoing->prepare($message, [], 'forward'), fn() => $outgoing->prepare($message, $actor, 'reply', ''), fn() => $outgoing->prepare($message, $actor, 'invalid'), fn() => $outgoing->prepare(array_replace($message, ['email_from' => 'invalid']), $actor, 'reply', 'Resposta')] as $operation) {
    try { $operation(); throw new LogicException('Invalid outgoing accepted'); }
    catch (InvalidArgumentException $e) {}
}
$smtpAccount = ['status' => 1, 'email_user' => 'account@example.test', 'password' => 'account-secret', 'mail_user' => 'mail.example.test'];
$smtp = $outgoing->smtpConfiguration($smtpAccount);
verifyService($smtp->SMTPUser === 'account@example.test' && $smtp->SMTPPass === 'account-secret' && $smtp->fromEmail === 'account@example.test', 'Account credentials and sender');
verifyService($smtp->SMTPHost === 'mail.example.test' && $smtp->SMTPPort === 587 && $smtp->SMTPCrypto === 'tls', 'Default server with SMTP port, not IMAP port');
$smtp = $outgoing->smtpConfiguration($smtpAccount + ['smtp_host' => 'smtp.example.test', 'smtp_port' => 465, 'smtp_security' => 'ssl']);
verifyService($smtp->SMTPHost === 'ssl://smtp.example.test' && $smtp->SMTPPort === 465 && $smtp->SMTPCrypto === '', 'Explicit account SMTP overrides');
try { $outgoing->smtpConfiguration(array_replace($smtpAccount, ['status' => 0])); throw new LogicException('Inactive account accepted'); }
catch (InvalidArgumentException $e) {}
echo "Outgoing mail: recipients, validation, reply composition and SMTP configuration guard passed (no messages sent).\n";
