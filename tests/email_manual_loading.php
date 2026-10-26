<?php
require __DIR__ . '/email_inbox.php';
require APPPATH . 'Database/Migrations/2026-10-26-000002_CreateEmailSent.php';
require APPPATH . 'Database/Migrations/2026-10-26-000003_CreateEmailMeetings.php';
(new App\Database\Migrations\CreateEmailSent(Config\Database::forge($db)))->up();
(new App\Database\Migrations\CreateEmailMeetings(Config\Database::forge($db)))->up();
// Route all implicit model connections to this test's isolated database.
$instances = new ReflectionProperty(CodeIgniter\Database\Config::class, 'instances');
$instances->setValue(null, ['tests' => $db]);
$accountId = $emails->saveFor(null, $data, $actor);
$rows->import($accountId, $message, $actor);
$stored = $rows->forAccount($accountId, $actor)->first();
session()->set('auth_user', $actor);
$request = Config\Services::incomingrequest(null, false);
$request->setGlobal('get', []);
$controller = new App\Controllers\Email();
$controller->initController($request, service('response'), service('logger'));
$html = $controller->account();
verifyService(str_contains($html, 'class="mail-row ') && !str_contains($html, 'Caixa de entrada aguardando atualização'), 'Initial navigation shows imported email list');
$html = $controller->account();
verifyService(str_contains($html, 'class="mail-row '), 'Repeated navigation keeps saved messages visible');
$foreignAccount = $emails->saveFor(null, array_replace($data, ['email_user' => 'foreign-inbox@example.test']), $other);
$rows->import($foreignAccount, array_replace($message, ['email_title' => 'Foreign private message', 'message_id' => '<foreign@example.test>']), $other);
$html = $controller->account();
verifyService(!str_contains($html, 'Foreign private message') && substr_count($html, 'class="mail-row ') === 1, 'Inbox includes only messages owned by the signed-in user');
$request->setGlobal('get', ['message' => (string) $stored['id']]);
$html = $controller->account();
verifyService(substr_count($html, 'class="mail-row ') === 1, 'Explicit message link loads only the requested saved email');
echo "Stored inbox loading: initial page, repeated navigation, ownership and direct message link passed.\n";
