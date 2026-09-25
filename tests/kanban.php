<?php
ob_start();
define('ENVIRONMENT', 'testing');
define('FCPATH', dirname(__DIR__) . '/public/');
require dirname(__DIR__) . '/app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
Config\Services::injectMock('request', Config\Services::incomingrequest(null, false));
set_exception_handler(static function (Throwable $error): void {
    fwrite(STDERR, $error->getMessage() . PHP_EOL); exit(1);
});
$db = Config\Database::connect(['DBDriver' => 'SQLite3', 'database' => ':memory:', 'DBPrefix' => 'test_', 'DBDebug' => true], false);
require APPPATH . 'Database/Migrations/2026-09-24-000006_CreateKanbanItems.php';
(new App\Database\Migrations\CreateKanbanItems(Config\Database::forge($db)))->up();
function verifyKanban(bool $condition, string $message): void {
    if (! $condition) { throw new RuntimeException($message); }
}
$model = new App\Models\KanbanModel($db);
$data = ['title' => '<script>alert(1)</script>', 'description' => 'Descrição', 'status' => 'todo', 'priority' => 'normal', 'user_id' => 'intruder'];
$id = $model->createFor('owner', $data);
verifyKanban($id !== false, 'Create');
verifyKanban($model->owned($id, 'owner')['user_id'] === 'owner', 'Owner cannot be supplied in payload');
verifyKanban($model->owned($id, 'other') === null && $model->forUser('other') === [], 'Private reads');
verifyKanban(! $model->updateFor($id, 'other', ['title' => 'Hacked']), 'Private writes');
foreach (array_keys($model::STATUSES) as $status) {
    verifyKanban($model->updateFor($id, 'owner', ['status' => $status]), 'Status update');
    verifyKanban($model->owned($id, 'owner')['status'] === $status, 'Status persisted');
}
verifyKanban(! $model->updateFor($id, 'owner', ['status' => 'invalid']), 'Invalid status rejected');
verifyKanban(! $model->updateFor($id, 'owner', ['priority' => 'invalid']), 'Invalid priority rejected');
verifyKanban($model->createFor('owner', array_replace($data, ['title' => ''])) === false, 'Title required');
foreach (array_keys($model::PRIORITIES) as $priority) {
    verifyKanban($model->updateFor($id, 'owner', ['priority' => $priority, 'user_id' => 'other']), 'Priority update');
}
$item = $model->owned($id, 'owner');
verifyKanban($item !== null, 'Ownership preserved');
$columns = array_fill_keys(array_keys($model::STATUSES), []);
$columns[$item['status']][] = $item;
$html = view('kanban/index', ['columns' => $columns]);
verifyKanban(! str_contains($html, 'kanban-close') && ! str_contains($html, '&lt;script&gt;'), 'Closed column and cards hidden');
$columns['todo'][] = $item;
$html = view('kanban/index', ['columns' => $columns]);
verifyKanban(! str_contains($html, '<script>alert(1)</script>') && str_contains($html, '&lt;script&gt;'), 'Card escapes HTML');
$html = view('kanban/form', ['item' => $item]);
verifyKanban(str_contains($html, '/update') && str_contains($html, 'Editar cartão'), 'Edit form');
verifyKanban(str_contains(view('kanban/form', ['item' => null]), 'Novo cartão'), 'New form');
echo "Kanban: ownership, statuses, priorities, validation and views passed.\n";
$linked = \App\Libraries\KanbanText::render('Veja https://example.org/path?a=1&b=2. <script>alert(1)</script> javascript:alert(1)');
verifyKanban(substr_count($linked, '<a ') === 1, 'Only HTTPS becomes a link');
verifyKanban(str_contains($linked, 'target="_blank"') && str_contains($linked, 'rel="noopener noreferrer"'), 'New-tab links protected');
verifyKanban(! str_contains($linked, '<script>') && str_contains($linked, '&lt;script&gt;'), 'Detail HTML escaped');
echo "Kanban detail links passed.\n";