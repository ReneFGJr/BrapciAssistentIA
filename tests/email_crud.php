<?php
require __DIR__ . '/user_services.php';
set_exception_handler(static function (Throwable $e): void { fwrite(STDERR, $e->getMessage() . PHP_EOL . $e->getTraceAsString()); exit(1); });
require APPPATH . 'Database/Migrations/2026-10-15-000001_CreateEmailUser.php';
(new App\Database\Migrations\CreateEmailUser(Config\Database::forge($db)))->up();
require APPPATH . 'Database/Migrations/2026-10-15-000003_AddEmailSmtp.php';
(new App\Database\Migrations\AddEmailSmtp(Config\Database::forge($db)))->up();
require APPPATH . 'Database/Migrations/2026-10-15-000004_AddEmailPreferred.php';
(new App\Database\Migrations\AddEmailPreferred(Config\Database::forge($db)))->up();
$emails = new App\Models\EmailUserModel($db);
$data = ['email_user' => 'mail@example.test', 'pass_user' => 'secret-test-password', 'mail_user' => 'imap.example.test', 'mail_port_user' => '993', 'protocol' => 'imap', 'security' => 'ssl', 'status' => '1'];
$id = $emails->saveFor(null, $data, $actor);
$row = $db->table('email_user')->where('id', $id)->get()->getRowArray();
verifyService($row['pass_user'] !== $data['pass_user'] && service('encrypter')->decrypt(base64_decode($row['pass_user'])) === $data['pass_user'], 'Encrypted password round trip');
verifyService(!array_key_exists('pass_user', $emails->findOwned($id, $actor)), 'Secret excluded from form');
verifyService($emails->forUser($other) === [], 'Other user list isolated');
foreach ([fn() => $emails->findOwned($id, $other), fn() => $emails->saveFor($id, $data, $other), fn() => $emails->deleteFor($id, $other)] as $operation) {
    try { $operation(); throw new LogicException('Cross-user access allowed'); }
    catch (CodeIgniter\Exceptions\PageNotFoundException $e) {}
}
foreach (['email_user' => 'invalid', 'mail_user' => 'https://server/path', 'mail_port_user' => '65536', 'protocol' => 'invalid', 'security' => 'invalid', 'status' => '2'] as $field => $invalid) {
    try { $emails->saveFor($id, array_replace($data, [$field => $invalid]), $actor); throw new LogicException('Invalid input accepted: ' . $field); }
    catch (InvalidArgumentException $e) {}
}
try { $emails->saveFor(null, $data, $actor); throw new LogicException('Duplicate allowed'); }
catch (InvalidArgumentException $e) {}
$emails->saveFor($id, array_replace($data, ['pass_user' => '', 'status' => '0']), $actor);
$updated = $db->table('email_user')->where('id', $id)->get()->getRowArray();
verifyService($updated['pass_user'] === $row['pass_user'] && (int) $updated['status'] === 0 && $updated['created'] === $row['created'], 'Blank password retained, status updated, creation preserved');
$html = view('email/index', ['accounts' => $emails->forUser($actor)]);
verifyService(str_contains($html, 'mail@example.test') && !str_contains($html, 'secret-test-password'), 'Listing without password');
$html = view('email/form', ['account' => $emails->findOwned($id, $actor)]);
verifyService(str_contains($html, 'csrf') && !str_contains($html, 'secret-test-password'), 'Form CSRF and secret exclusion');
$emails->deleteFor($id, $actor);
verifyService($emails->forUser($actor) === [], 'Deletion');
echo "Email CRUD: encryption, validation, ownership, views and deletion passed.\n";
