<?php
require __DIR__ . '/email_inbox.php';
require APPPATH . 'Database/Migrations/2026-10-26-000002_CreateEmailSent.php';
(new App\Database\Migrations\CreateEmailSent(Config\Database::forge($db)))->up();
$accountId = $emails->saveFor(null, $data, $actor);
$account = $emails->findOwned($accountId, $actor);
$rows->import($accountId, $message, $actor);
$original = $rows->forAccount($accountId, $actor)->first();
$sent = new App\Models\EmailSentModel($db);
$mail = ['to' => 'recipient@example.test', 'cc' => 'copy@example.test', 'subject' => 'Re: Teste', 'body' => "Resposta <script>alert(1)</script>\nMensagem original"];
$pendingId = $sent->recordPending($original, $actor, $account, 'reply_all', 'Minha resposta <script>alert(1)</script>', $mail);
verifyService($sent->forUser($actor) === [], 'Unconfirmed sends excluded from history');
$sent->confirmSent($pendingId, $other);
verifyService($sent->forUser($actor) === [], 'Other user cannot confirm a send');
$sent->confirmSent($pendingId, $actor);
$history = $sent->forUser($actor);
verifyService(count($history) === 1 && $history[0]['body'] === $mail['body'] && $history[0]['reply_text'] === 'Minha resposta <script>alert(1)</script>', 'Confirmed send preserves full content and response');
verifyService($history[0]['recipients'] === $mail['to'] && $history[0]['cc'] === $mail['cc'] && $history[0]['sent_at'] !== null, 'Recipients and timestamp preserved');
verifyService($sent->forUser($other) === [] && $sent->forUser([]) === [], 'Sent history isolated by owner');
try {
    $sent->recordPending($original, $other, $account, 'reply', 'Foreign response', $mail);
    throw new LogicException('Foreign send accepted');
} catch (CodeIgniter\Exceptions\PageNotFoundException $e) {}
$html = view('email/account', ['account' => $account, 'accounts' => [$account], 'messages' => [$original], 'sentMessages' => $history]);
verifyService(str_contains($html, 'Já respondido') && str_contains($html, 'Este e-mail já foi respondido') && str_contains($html, 'Minha resposta &lt;script&gt;') && !str_contains($html, '<script>alert'), 'Inbox shows answered flag and escaped reply content');
$html = view('email/sent', ['messages' => $history]);
verifyService(str_contains($html, 'E-mails enviados') && str_contains($html, 'copy@example.test') && !str_contains($html, '<script>alert'), 'Sent view shows recipients and safely escaped body');
$html = view('email/sent', ['messages' => []]);
verifyService(str_contains($html, 'Nenhum e-mail enviado registrado'), 'Empty sent history');
echo "Sent email: confirmation, ownership, response history and safe views passed (no messages sent).\n";
