<?php
require __DIR__ . '/user_services.php';
require APPPATH . 'Database/Migrations/2026-09-30-000005_CreateSubjects.php';
require APPPATH . 'Database/Migrations/2026-09-30-000008_CreateServiceGoogleSchedule.php';
// Only subjects are needed here; no note associations are used.
$db->query('CREATE TABLE test_subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id VARCHAR(191), name VARCHAR(150))');
(new App\Database\Migrations\CreateServiceGoogleSchedule(Config\Database::forge($db)))->up();
$model->saveGoogle($actor, 'owner@example.test', 'Test_Api_Key');
$connection = $model->googleConnection($actor);
verifyService($model->hasGoogle($actor) && $connection['user_id'] === 'owner', 'Active service');
$otherConnection = $model->googleConnection($other);
$calendar = new App\Models\GoogleScheduleModel($db);
$day = gmdate('Y-m-d', time() + 86400);
$event = App\Libraries\GoogleCalendarClient::normalize([
    'id' => 'event1', 'summary' => '<b>Reunião</b>', 'location' => 'Sala <A>', 'start' => ['dateTime' => $day . 'T10:00:00-03:00'],
    'end' => ['dateTime' => $day . 'T11:00:00-03:00'], 'status' => 'confirmed',
], 'America/Sao_Paulo');
verifyService($event['starts_at'] === $day . ' 13:00:00', 'Timezone normalized to UTC');
verifyService($event['title'] === '<b>Reunião</b>' && $event['location'] === 'Sala <A>', 'Title and location imported');
$calendar->replaceSnapshot($connection, [$event]);
$events = $calendar->upcoming($connection)->findAll();
verifyService(count($events) === 1, 'Upcoming event stored');
verifyService($calendar->upcoming($otherConnection)->findAll() === [], 'User isolation');
$db->table('subjects')->insert(['user_id' => 'owner', 'name' => 'Pesquisa']);
$subject = (int) $db->insertID();
$db->table('subjects')->insert(['user_id' => 'other', 'name' => 'Privado']);
$foreignSubject = (int) $db->insertID();
verifyService($calendar->assignSubject((int) $events[0]['id'], $subject, $connection), 'Assign own subject');
try {
    $calendar->assignSubject((int) $events[0]['id'], $foreignSubject, $connection);
    throw new LogicException('Foreign subject allowed');
} catch (CodeIgniter\Exceptions\PageNotFoundException $exception) {}
try {
    $calendar->assignSubject((int) $events[0]['id'], null, $otherConnection);
    throw new LogicException('Foreign event allowed');
} catch (CodeIgniter\Exceptions\PageNotFoundException $exception) {}
$calendar->replaceSnapshot($connection, [array_replace($event, ['title' => 'Atualizada'])]);
$events = $calendar->upcoming($connection)->findAll();
verifyService(count($events) === 1 && (int) $events[0]['subject_id'] === $subject, 'Update avoids duplicates and retains subject');
$allDay = App\Libraries\GoogleCalendarClient::normalize([
    'id' => 'day1', 'start' => ['date' => $day], 'end' => ['date' => gmdate('Y-m-d', time() + 172800)],
], 'America/Sao_Paulo');
verifyService($allDay['all_day'] === 1 && substr($allDay['starts_at'], 11) === '03:00:00', 'All day local midnight');
$calendar->replaceSnapshot($connection, [$allDay]);
verifyService(count($calendar->upcoming($connection)->findAll()) === 1, 'Removed events omitted');
$calendar->replaceSnapshot($connection, []);
verifyService($calendar->upcoming($connection)->findAll() === [], 'Empty snapshot removes stale upcoming events');
$calendar->replaceSnapshot($connection, [$event]);
$before = $calendar->upcoming($connection)->findAll();
try {
    $calendar->replaceSnapshot($connection, [['google_event_id' => 'bad']]);
} catch (Throwable $exception) {}
verifyService($calendar->upcoming($connection)->findAll() === $before, 'Failed snapshot rolled back');
$html = view('schedule/index', ['events' => $before, 'subjects' => [], 'lastSync' => null, 'pager' => new class { public function links() { return ''; } }]);
verifyService(str_contains($html, '&lt;b&gt;Reunião&lt;/b&gt;') && !str_contains($html, 'Test_Api_Key'), 'Escaped events and no key in view');
verifyService(str_contains($html, 'Sala &lt;A&gt;') && str_contains($html, 'Título da reunião'), 'Location and explicit title heading rendered');
$missing = array_replace($before[0], ['title' => 'Sem título', 'location' => '']);
$missingHtml = view('schedule/index', ['events' => [$missing], 'subjects' => [], 'lastSync' => null, 'pager' => new class { public function links() { return ''; } }]);
verifyService(str_contains($missingHtml, 'Título não fornecido pelo Google') && str_contains($missingHtml, 'Não informado pelo Google'), 'Missing details explicit');
$model->deleteFor($connection['id'], $actor);
verifyService(!$model->hasGoogle($actor), 'Service removal disables access');
echo "Schedule: times, isolation, subjects, sync and view checks passed.\n";
