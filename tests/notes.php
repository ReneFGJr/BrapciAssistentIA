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
    fwrite(STDERR, $error . PHP_EOL); exit(1);
});
$db = Config\Database::connect(['DBDriver' => 'SQLite3', 'database' => ':memory:', 'DBPrefix' => 'test_', 'DBDebug' => true, 'foreignKeys' => true], false);
foreach (['2026-09-24-000001_CreatePersons' => 'CreatePersons', '2026-09-24-000002_CreatePersonsUser' => 'CreatePersonsUser', '2026-09-30-000001_CreateNotes' => 'CreateNotes', '2026-09-30-000003_AddNotesTimeAndParticipants' => 'AddNotesTimeAndParticipants', '2026-09-30-000004_AllowNotesWithoutPerson' => 'AllowNotesWithoutPerson'] as $file => $class) {
    require APPPATH . 'Database/Migrations/' . $file . '.php';
    $class = 'App\\Database\\Migrations\\' . $class;
    (new $class(Config\Database::forge($db)))->up();
}
function verifyNote(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
function deniedNote(callable $operation): void {
    try { $operation(); } catch (CodeIgniter\Exceptions\PageNotFoundException $e) { return; }
    throw new RuntimeException('Unauthorized operation allowed');
}
$service = new App\Libraries\PersonAccessService($db);
$owner = ['id' => 'owner', 'email' => 'owner@example.test'];
$reader = ['id' => 'reader', 'email' => 'reader@example.test'];
$stranger = ['id' => 'stranger', 'email' => 'stranger@example.test'];
$person = $service->create(['nickname' => 'Ana', 'full_name' => 'Ana Silva'], $owner);
$other = $service->create(['nickname' => 'Bruno', 'full_name' => 'Bruno Silva'], $stranger);
$service->share($person, $owner, $reader['email'], 'read', '');
$model = new App\Models\NoteModel($db);
$data = ['person_id' => $person, 'meeting_date' => '2026-09-29', 'title' => '<script>alert(1)</script>', 'description' => 'Descrição', 'status' => 'scheduled'];
verifyNote($model->saveFor($owner, $data), 'Create');
$id = (int) $model->getInsertID();
verifyNote($model->saveFor($owner, array_replace($data, ['meeting_date' => '2026-09-30'])), 'Create recent');
$recentId = (int) $model->getInsertID();
$visible = $model->visibleTo($reader)->findAll();
verifyNote(count($visible) === 2 && (int) $visible[0]['id'] === $recentId, 'Read share and descending order');
verifyNote($model->visibleTo($stranger)->findAll() === [], 'Unshared notes invisible');
deniedNote(fn () => $model->saveFor($reader, $data));
deniedNote(fn () => $model->saveFor($reader, $data, $id));
deniedNote(fn () => $model->saveFor($stranger, $data, $id));
deniedNote(fn () => $model->saveFor($owner, array_replace($data, ['person_id' => $other]), $id));
foreach (['status' => 'invalid', 'meeting_date' => '2026-02-30', 'title' => '', 'description' => '', 'person_id' => '0'] as $key => $value) {
    verifyNote(!$model->saveFor($owner, array_replace($data, [$key => $value]), $id), 'Reject invalid ' . $key);
}
verifyNote($model->saveFor($owner, array_replace($data, ['status' => 'completed']), $id), 'Edit');
verifyNote($model->find($id)['status'] === 'completed', 'Edit persisted');
$service->share($person, $owner, $reader['email'], 'edit', '');
verifyNote($model->saveFor($reader, $data, $id), 'Shared editor');
$db->table('persons_user')->where('user', 'reader')->update(['expires_at' => '2000-01-01 00:00:00']);
verifyNote($model->visibleTo($reader)->findAll() === [], 'Expired read denied');
deniedNote(fn () => $model->saveFor($reader, $data, $id));
$html = view('notes/index', ['notes' => $model->visibleTo($owner)->findAll(), 'editableIds' => [$person], 'pager' => new class { public function links() { return ''; } }]);
verifyNote(!str_contains($html, '<script>alert(1)</script>') && str_contains($html, '&lt;script&gt;'), 'Escaped notes');
$html = view('notes/form', ['note' => $model->find($id), 'persons' => (new App\Models\PersonModel($db))->findAll()]);
verifyNote(!str_contains($html, 'name="person_id"') && str_contains($html, 'csrf'), 'Autocomplete and CSRF form');
ob_end_clean();
echo "Notes checks passed: create, edit, validation, ordering, access, expiration and escaping.\n";
