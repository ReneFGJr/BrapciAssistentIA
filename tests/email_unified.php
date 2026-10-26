<?php
require __DIR__ . '/email_inbox.php';
$firstId = $emails->saveFor(null, array_replace($data, ['email_user' => 'first@example.test']), $actor);
$secondId = $emails->saveFor(null, array_replace($data, ['email_user' => 'second-unified@example.test']), $actor);
$foreignId = $emails->saveFor(null, array_replace($data, ['email_user' => 'foreign@example.test']), $other);
$rows->import($firstId, array_replace($message, ['remote_key' => hash('sha256', 'first'), 'email_data' => '2026-10-15 10:00:00']), $actor);
$rows->import($secondId, array_replace($message, ['remote_key' => hash('sha256', 'second'), 'email_data' => '2026-10-15 12:00:00']), $actor);
$rows->import($foreignId, array_replace($message, ['remote_key' => hash('sha256', 'foreign'), 'email_data' => '2026-10-15 14:00:00']), $other);
$unified = $rows->forUser($actor)->findAll();
verifyService(count($unified) === 2, 'Only messages owned by current user');
verifyService((int) $unified[0]['email_user_id'] === $secondId && (int) $unified[1]['email_user_id'] === $firstId, 'Merged chronological order');
$page = $rows->forUser($actor)->paginate(25);
$html = view('email/account', ['account' => $emails->findOwned($firstId, $actor), 'accounts' => $emails->forUser($actor), 'messages' => $page, 'pager' => $rows->pager]);
foreach ($unified as $entry) {
    $base = 'email/account/' . $entry['email_user_id'] . '/messages/' . $entry['id'];
    verifyService(str_contains($html, $base . '/status') && str_contains($html, $base . '/send') && str_contains($html, $base . '/delete'), 'Actions target actual source account');
    verifyService(str_contains($html, 'data-account="' . $entry['email_user_id'] . '"'), 'Automatic reads and attachments know source account');
}
verifyService(!str_contains($html, 'foreign@example.test'), 'Foreign account not rendered');
verifyService(str_contains($html, 'first@example.test') && str_contains($html, 'second-unified@example.test'), 'Source accounts identified');
verifyService(str_contains($html, 'name="manual_sync" value="1"')
    && str_contains($html, "{manual_sync: '1'}"), 'Inbox sync requires the explicit Update action');
verifyService(str_contains($html, 'Consultar anexos') && str_contains($html, 'Clique em Atualizar para consultar novos e-mails'), 'Server queries presented as explicit actions');
$reader = substr($html, strpos($html, "rows.forEach(row => row.addEventListener('click'"));
$reader = substr($reader, 0, strpos($reader, 'function apply'));
verifyService(!str_contains($reader, 'loadAttachments(') && !str_contains($reader, "post('/sync'"), 'Opening or restoring a message uses stored data without querying mail server');
verifyService(!str_contains($html, "element.setAttribute('sandbox'"), 'PDF preview is not blocked by Chromium sandbox restrictions');
verifyService(str_contains($html, "element.referrerPolicy = 'no-referrer'"), 'PDF preview does not send a referrer');
echo "Unified inbox: ownership, chronological order and account-specific actions passed.\n";
