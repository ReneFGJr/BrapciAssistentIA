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
foreach ([$smtpAccount, $smtpAccount + ['smtp_host' => '']] as $missingSmtp) {
    try { $outgoing->smtpConfiguration($missingSmtp); throw new LogicException('IMAP fallback accepted for SMTP'); }
    catch (InvalidArgumentException $e) { verifyService(str_contains($e->getMessage(), 'Servidor SMTP'), 'Missing SMTP gives configuration guidance'); }
}
$smtp = $outgoing->smtpConfiguration($smtpAccount + ['smtp_host' => 'smtp.example.test']);
verifyService($smtp->SMTPUser === 'account@example.test' && $smtp->SMTPPass === 'account-secret' && $smtp->fromEmail === 'account@example.test', 'Account credentials and sender');
verifyService($smtp->SMTPHost === 'smtp.example.test' && $smtp->SMTPPort === 587 && $smtp->SMTPCrypto === 'tls', 'Explicit SMTP server with default port and TLS, independent of IMAP');
$smtp = $outgoing->smtpConfiguration($smtpAccount + ['smtp_host' => 'smtp.example.test', 'smtp_port' => 465, 'smtp_security' => 'ssl']);
verifyService($smtp->SMTPHost === 'ssl://smtp.example.test' && $smtp->SMTPPort === 465 && $smtp->SMTPCrypto === '', 'Explicit account SMTP overrides');
$smtpId = $emails->saveFor(null, array_replace($data, ['smtp_host' => 'smtp.persisted.example.test', 'smtp_port' => '587', 'smtp_security' => 'tls']), $actor);
$persistedSmtp = $outgoing->smtpConfiguration($emails->connection($smtpId, $actor));
verifyService($persistedSmtp->SMTPHost === 'smtp.persisted.example.test' && $persistedSmtp->SMTPPort === 587 && $persistedSmtp->SMTPCrypto === 'tls', 'SMTP host, port and security read from account database columns');
$emails->deleteFor($smtpId, $actor);
try { $outgoing->smtpConfiguration(array_replace($smtpAccount, ['status' => 0])); throw new LogicException('Inactive account accepted'); }
catch (InvalidArgumentException $e) {}
echo "Outgoing mail: recipients, validation, reply composition and SMTP configuration guard passed (no messages sent).\n";

$allMessage = array_replace($message, ['email_reply_to' => 'Support <support@example.test>', 'email_to' => 'owner@example.test, Other <other@example.test>, SUPPORT@example.test', 'email_cc' => 'other@example.test, Copy <copy@example.test>, OWNER@example.test']);
$all = $outgoing->prepare($allMessage, $actor, 'reply_all', 'Resposta coletiva');
verifyService($all['to'] === 'support@example.test, other@example.test' && $all['cc'] === 'copy@example.test', 'Reply all respects Reply-To, excludes sending account and deduplicates To/Cc');
$single = $outgoing->prepare($allMessage, $actor, 'reply', 'Resposta');
verifyService($single['to'] === 'support@example.test' && $single['cc'] === '', 'Individual reply respects Reply-To without copying others');
