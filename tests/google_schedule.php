<?php
require __DIR__ . '/user_services.php';
set_exception_handler(static function (Throwable $e): void { fwrite(STDERR, $e->getMessage() . PHP_EOL . $e->getTraceAsString()); exit(1); });
require APPPATH . 'Database/Migrations/2026-09-30-000005_CreateSubjects.php';
require APPPATH . 'Database/Migrations/2026-09-30-000008_CreateServiceGoogleSchedule.php';
// Only subjects are needed here; no note associations are used.
$db->query('CREATE TABLE test_subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id VARCHAR(191), name VARCHAR(150))');
(new App\Database\Migrations\CreateServiceGoogleSchedule(Config\Database::forge($db)))->up();
require APPPATH . 'Database/Migrations/2026-10-27-000001_AddScheduleLinks.php';
(new App\Database\Migrations\AddScheduleLinks(Config\Database::forge($db)))->up();
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
verifyService(count($calendar->upcomingForUser('owner')->findAll()) === 1
    && $calendar->latestSyncForUser('owner') !== null, 'Stored schedule available by user');
verifyService($calendar->upcoming($otherConnection)->findAll() === [], 'User isolation');
$db->table('subjects')->insert(['user_id' => 'owner', 'name' => 'Pesquisa']);
$subject = (int) $db->insertID();
$db->table('subjects')->insert(['user_id' => 'other', 'name' => 'Privado']);
$foreignSubject = (int) $db->insertID();
verifyService($calendar->assignSubject((int) $events[0]['id'], $subject, $connection), 'Assign own subject');
verifyService($calendar->assignSubjectForUser((int) $events[0]['id'], $subject, 'owner'), 'Assign subject from consolidated schedule');
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
$rendered = array_replace($before[0], ['description' => 'Descrição detalhada do evento']);
$html = view('schedule/index', ['events' => [$rendered], 'subjects' => [], 'lastSync' => null, 'pager' => new class { public function links() { return ''; } }]);
verifyService(str_contains($html, '&lt;b&gt;Reunião&lt;/b&gt;') && !str_contains($html, 'Test_Api_Key'), 'Escaped events and no key in view');
verifyService(str_contains($html, 'schedule/sync') && str_contains($html, 'Consultando o Google'), 'Update action explicitly starts Google synchronization');
verifyService(str_contains($html, 'Descrição detalhada do evento')
    && str_contains($html, (new DateTimeImmutable($rendered['starts_at'], new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone($rendered['timezone']))->format('d/m/Y')), 'Event description appears in details and events remain grouped by day');
verifyService((bool) preg_match('/\d{2}\/\d{2}\/\d{4} \((Segunda|Terça|Quarta|Quinta|Sexta)-feira|Sábado|Domingo\)/u', $html), 'Weekday rendered in Portuguese');
verifyService(str_contains($html, 'Sala &lt;A&gt;') && str_contains($html, 'Título da reunião'), 'Location and explicit title heading rendered');
$linked = array_replace($before[0], ['location' => 'https://meet.example.test/room', 'description' => 'Detalhes em https://meet.example.test/info']);
$linkedHtml = view('schedule/index', ['events' => [$linked], 'subjects' => [], 'lastSync' => null, 'pager' => new class { public function links() { return ''; } }]);
verifyService(substr_count($linkedHtml, 'target="_blank"') >= 3
    && str_contains($linkedHtml, 'bi-link-45deg')
    && str_contains($linkedHtml, 'data-bs-target="#schedule-event-'), 'Event details open in a modal and URLs are external links');
$conference = App\Libraries\GoogleCalendarClient::normalize([
    'id' => 'conference-event', 'summary' => 'Reunião BPPQ', 'location' => 'Sala 204',
    'description' => '<p><strong>Observação importante</strong><br>Levar documentos</p>',
    'start' => ['dateTime' => $day . 'T09:00:00-03:00'], 'end' => ['dateTime' => $day . 'T10:00:00-03:00'],
    'hangoutLink' => 'https://meet.google.com/abc-defg-hij',
    'htmlLink' => 'https://www.google.com/calendar/event?eid=meeting',
], 'America/Sao_Paulo');
verifyService($conference['meeting_url'] === 'https://meet.google.com/abc-defg-hij' && $conference['location'] === 'Sala 204', 'Google conference URL retained separately from physical location');
$db->resetTransStatus(); // Previous test intentionally failed and rolled back a snapshot.
$calendar->replaceSnapshot($connection, [$conference]);
$storedConference = $calendar->upcoming($connection)->first();
verifyService($storedConference['meeting_url'] === $conference['meeting_url'] && $storedConference['google_url'] === $conference['google_url'], 'Conference and Google event links survive synchronization');
$details = view('schedule/index', ['events' => [$storedConference], 'subjects' => [], 'lastSync' => null, 'pager' => new class { public function links() { return ''; } }]);
verifyService(str_contains($details, 'Entrar com o Google Meet') && str_contains($details, 'Sala 204') && str_contains($details, 'Abrir no Google Agenda') && str_contains($details, 'Observações da reunião'), 'Details display video button, location, event link and isolated formatted notes');
verifyService(str_contains(html_entity_decode(html_entity_decode($details, ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'Observação importante'), 'Formatted observation content retained');
verifyService(App\Libraries\GoogleCalendarClient::meetingUrl(['conferenceData' => ['entryPoints' => [['entryPointType' => 'phone', 'uri' => 'tel:123'], ['entryPointType' => 'video', 'uri' => 'https://meet.google.com/xyz-aaaa-bbb']]]]) === 'https://meet.google.com/xyz-aaaa-bbb', 'Video conference selected from entry points');
verifyService(App\Libraries\GoogleCalendarClient::meetingUrl(['hangoutLink' => 'javascript:alert(1)']) === '', 'Unsafe conference link rejected');
$missing = array_replace($before[0], ['title' => 'Sem título', 'location' => '']);
$missingHtml = view('schedule/index', ['events' => [$missing], 'subjects' => [], 'lastSync' => null, 'pager' => new class { public function links() { return ''; } }]);
verifyService(str_contains($missingHtml, 'Título não fornecido pelo Google') && !str_contains($missingHtml, 'Não informado pelo Google'), 'Missing location rendered empty');
$model->deleteFor($connection['id'], $actor);
verifyService(!$model->hasGoogle($actor), 'Service removal disables access');
echo "Schedule: times, isolation, subjects, sync and view checks passed.\n";
