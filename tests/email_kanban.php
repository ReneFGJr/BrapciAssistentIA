<?php
require __DIR__ . '/email_inbox.php';
$webConfig = new Config\App();
$webConfig->baseURL = 'https://example.test/sudo/';
config(Config\App::class)->baseURL = $webConfig->baseURL;
Config\Services::injectMock('request', new CodeIgniter\HTTP\IncomingRequest(
    $webConfig, new CodeIgniter\HTTP\SiteURI($webConfig), null, new CodeIgniter\HTTP\UserAgent()
));
require APPPATH . 'Database/Migrations/2026-09-24-000006_CreateKanbanItems.php';
(new App\Database\Migrations\CreateKanbanItems(Config\Database::forge($db)))->up();
$kanban = new App\Models\KanbanModel($db);
$accountId = $emails->saveFor(null, $data, $actor);
$rows->import($accountId, array_replace($message, [
    'email_title' => 'Preparar reunião <script>alert(1)</script>',
    'email_context' => 'Enviar relatório com as alterações.',
]), $actor);
$mail = $rows->forAccount($accountId, $actor)->first();
$draft = $kanban->emailDraft($accountId, (int) $mail['id'], $actor);
verifyService(str_contains($draft['description'], 'Enviar relatório') && str_contains($draft['email_url'], '?message=' . $mail['id']), 'Draft contains email text and direct reference');
$task = ['title' => $draft['title'], 'description' => 'Minha tarefa', 'status' => 'todo', 'priority' => 'normal', 'user_id' => $other['id']];
$taskId = $kanban->createForEmail($accountId, (int) $mail['id'], $actor, $task);
$saved = $kanban->owned((int) $taskId, (string) $actor['id']);
verifyService($taskId !== false && str_contains($saved['description'], $draft['email_url']), 'Saved task retains email reference even when description is replaced');
verifyService($kanban->owned((int) $taskId, (string) $other['id']) === null, 'Task belongs to email owner');
$withReference = $kanban->createForEmail($accountId, (int) $mail['id'], $actor, array_replace($task, ['description' => $draft['description']]));
verifyService(substr_count($kanban->owned((int) $withReference, (string) $actor['id'])['description'], $draft['email_url']) === 1, 'Reference not duplicated');
foreach ([
    fn () => $kanban->emailDraft($accountId, (int) $mail['id'], $other),
    fn () => $kanban->createForEmail($accountId, (int) $mail['id'], $other, $task),
    fn () => $kanban->emailDraft($accountId + 1000, (int) $mail['id'], $actor),
] as $operation) {
    try { $operation(); throw new RuntimeException('Foreign or mismatched email accepted'); }
    catch (CodeIgniter\Exceptions\PageNotFoundException $e) {}
}
verifyService($kanban->createForEmail($accountId, (int) $mail['id'], $actor, array_replace($task, ['title' => ''])) === false, 'Task validation retained');
$form = view('kanban/form', ['item' => null, 'draft' => $draft, 'emailSource' => [$accountId, (int) $mail['id']]]);
verifyService(str_contains($form, 'name="email_message"') && str_contains($form, '&lt;script&gt;') && !str_contains($form, '<script>alert(1)</script>'), 'Form preserves source and escapes email title');
$page = view('email/account', ['account' => $emails->findOwned($accountId, $actor), 'messages' => [$mail]]);
verifyService(str_contains($page, 'Criar tarefa no Kanban') && str_contains($page, 'email_message=' . $mail['id']), 'Email toolbar links to task creation without requiring calendar connection');
verifyService(str_contains(App\Libraries\KanbanText::render($saved['description']), 'href="'), 'Kanban description renders clickable reference');
echo "Email Kanban: draft, task creation, reference, ownership, validation and rendering passed.\n";
