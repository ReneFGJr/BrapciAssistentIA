<?php
require __DIR__ . '/user_services.php';
$db->query('PRAGMA foreign_keys = ON');
$db->query('CREATE TABLE test_subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id VARCHAR(191), name VARCHAR(150), UNIQUE(user_id, name))');
require APPPATH . 'Database/Migrations/2026-09-30-000008_CreateServiceGoogleSchedule.php';
(new App\Database\Migrations\CreateServiceGoogleSchedule(Config\Database::forge($db)))->up();
$db->table('subjects')->insert(['user_id' => 'other', 'name' => 'Privado']);
$foreignSubject = (int) $db->insertID();
$calendar = new App\Models\GoogleScheduleModel($db);
$day = gmdate('Y-m-d', time() + 86400);
$event = App\Libraries\GoogleCalendarClient::normalize(['id' => 'crud-event', 'summary' => 'Meeting', 'start' => ['dateTime' => $day . 'T10:00:00Z'], 'end' => ['dateTime' => $day . 'T11:00:00Z']], 'UTC');
set_exception_handler(static function (Throwable $e): void { fwrite(STDERR, $e->getMessage() . PHP_EOL . $e->getTraceAsString()); exit(1); });
$subjects = new App\Models\SubjectModel($db);
$id = $subjects->saveFor(null, '  Novo   assunto  ', $actor);
verifyService($subjects->findOwned($id, $actor)['name'] === 'Novo assunto', 'Create and normalize subject');
$subjects->saveFor($id, 'Renomeado', $actor);
verifyService($subjects->findOwned($id, $actor)['name'] === 'Renomeado', 'Rename subject');
foreach ([
    fn() => $subjects->findOwned($id, $other),
    fn() => $subjects->saveFor($id, 'Invadido', $other),
    fn() => $subjects->deleteFor($id, $other),
] as $operation) {
    try { $operation(); throw new LogicException('Cross-user access allowed'); }
    catch (CodeIgniter\Exceptions\PageNotFoundException $exception) {}
}
foreach (['', str_repeat('x', 151), 'Renomeado'] as $name) {
    try { $subjects->saveFor(null, $name, $actor); throw new LogicException('Invalid subject accepted'); }
    catch (InvalidArgumentException $exception) {}
}
$model->saveGoogle($actor, 'owner@example.test', 'Test_Api_Key');
$connection = $model->googleConnection($actor);
$calendar->replaceSnapshot($connection, [$event]);
$stored = $calendar->upcoming($connection)->first();
$calendar->assignSubject((int) $stored['id'], $id, $connection);
$db->query('CREATE TABLE test_note_subjects (note_id INTEGER, subject_id INTEGER REFERENCES test_subjects(id) ON DELETE CASCADE)');
$db->table('note_subjects')->insert(['note_id' => 12, 'subject_id' => $id]);
$subjects->deleteFor($id, $actor);
verifyService($db->table('note_subjects')->where('subject_id', $id)->countAllResults() === 0, 'Note links removed');
$stored = $calendar->upcoming($connection)->first();
verifyService($stored !== null && $stored['subject_id'] === null, 'Meeting retained with subject cleared');
verifyService($subjects->findOwned($foreignSubject, $other)['name'] === 'Privado', 'Other user preserved');
echo "Subjects CRUD: validation, ownership and relationship checks passed.\n";
