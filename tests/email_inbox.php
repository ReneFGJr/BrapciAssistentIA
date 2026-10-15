<?php
require __DIR__ . '/email_crud.php';
require APPPATH . 'Database/Migrations/2026-10-15-000002_CreateEmailUserRow.php';
$db->query('PRAGMA foreign_keys = ON');
$migration = new App\Database\Migrations\CreateEmailUserRow(Config\Database::forge($db));
$migration->up();
require APPPATH . 'Database/Migrations/2026-10-15-000005_AddEmailServerDeleted.php';
(new App\Database\Migrations\AddEmailServerDeleted(Config\Database::forge($db)))->up();
$id = $emails->saveFor(null, $data, $actor);
$connection = $emails->connection($id, $actor);
verifyService($connection['password'] === $data['pass_user'], 'Server-only decrypted connection');
$rows = new App\Models\EmailUserRowModel($db);
$message = ['remote_key' => hash('sha256', 'uidvalidity:123:uid:1'), 'message_id' => '<test@example.test>', 'email_from' => '<sender@example.test>', 'email_to' => 'mail@example.test', 'email_title' => '<script>alert(1)</script>', 'email_context' => '<img src=x onerror=alert(1)>', 'email_data' => '2026-10-15 12:00:00', 'email_status' => 0, 'size_bytes' => 100, 'has_attachments' => 0];
verifyService($rows->import($id, $message, $actor), 'First import');
verifyService(!$rows->import($id, $message, $actor), 'Duplicate skipped');
$stored = $rows->forAccount($id, $actor)->first();
$messageId = (int) $stored['id'];
verifyService($rows->markRead($id, $messageId, $actor) === 1, 'Automatic read changes unread to read');
verifyService($rows->markRead($id, $messageId, $actor) === 1, 'Automatic read is idempotent');
$rows->changeStatus($id, $messageId, '2', $actor);
verifyService($rows->markRead($id, $messageId, $actor) === 2, 'Automatic read preserves important classification');
$rows->changeStatus($id, $messageId, '0', $actor);
try { $rows->markRead($id, $messageId, $other); throw new LogicException('Foreign auto-read accepted'); }
catch (CodeIgniter\Exceptions\PageNotFoundException $e) {}
foreach ([fn() => $rows->forAccount($id, $other), fn() => $rows->import($id, $message, $other), fn() => $rows->changeStatus($id, $messageId, '3', $other)] as $operation) {
    try { $operation(); throw new LogicException('Foreign access accepted'); }
    catch (CodeIgniter\Exceptions\PageNotFoundException $e) {}
}
$rows->changeStatus($id, $messageId, '3', $actor);
verifyService($rows->markRead($id, $messageId, $actor) === 3, 'Automatic read preserves urgent classification');
verifyService(!$rows->import($id, $message, $actor) && (int) $rows->forAccount($id, $actor)->first()['email_status'] === 3, 'Local urgent status preserved');
try { $rows->changeStatus($id, $messageId, '9', $actor); throw new LogicException('Invalid status accepted'); }
catch (InvalidArgumentException $e) {}
$otherId = $emails->saveFor(null, array_replace($data, ['email_user' => 'second@example.test']), $actor);
try { $rows->changeStatus($otherId, $messageId, '1', $actor); throw new LogicException('Wrong account accepted'); }
catch (CodeIgniter\Exceptions\PageNotFoundException $e) {}
$page = $rows->forAccount($id, $actor)->paginate(25);
$html = view('email/account', ['account' => $emails->findOwned($id, $actor), 'messages' => $page, 'pager' => $rows->pager]);
verifyService(!str_contains($html, '<script>alert') && !str_contains($html, '<img src=x') && str_contains($html, 'bi-arrow-clockwise') && str_contains($html, 'csrf'), 'Safe content, update icon and CSRF');
verifyService(App\Libraries\EmailInbox::decodeHeader('=?UTF-8?B?T2zDoQ==?=') === 'Olá', 'MIME header decoding');
$emails->deleteFor($id, $actor);
verifyService($db->table('email_user_row')->countAllResults() === 0, 'Messages cascade on account deletion');
echo "Email inbox: import, deduplication, ownership, status, safe rendering and cascade passed.\n";
