<?php
require __DIR__ . '/email_inbox.php';

use App\Libraries\EmailMeeting;
use App\Libraries\GoogleCalendarClient;
use App\Libraries\GoogleCalendarException;
use App\Models\EmailMeetingModel;
use App\Models\PrivateGoogleServiceModel;

require APPPATH . 'Database/Migrations/2026-10-26-000003_CreateEmailMeetings.php';
(new App\Database\Migrations\CreateEmailMeetings(Config\Database::forge($db)))->up();
$private = new PrivateGoogleServiceModel($db);
$private->saveConfiguration($actor, 'owner@example.test', 'meet-client.apps.googleusercontent.com', 'test-secret');
$service = $private->connection($actor);
$service = ['id' => $service['id'], 'user_id' => $actor['id'], 'email' => 'primary', 'access_token' => 'test-access', 'can_write' => true];
$accountId = $emails->saveFor(null, $data, $actor);
$rows->import($accountId, $message, $actor);
$original = $rows->forAccount($accountId, $actor)->first();
$day = (new DateTimeImmutable('tomorrow', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
$values = ['title' => 'Reunião <teste>', 'start' => $day . 'T09:00', 'end' => $day . 'T10:00', 'location' => 'Sala A', 'guests' => 'guest@example.test, GUEST@example.test; other@example.test', 'meet' => '1'];
$reference = 'https://example.test/sudo/index.php/email/account?message=' . $original['id'];
$payload = EmailMeeting::payload($original, $values, $reference);
foreach (['15', '30', '60', '120', '180'] as $duration) {
    $timed = EmailMeeting::payload($original, array_replace($values, ['start' => $day . 'T23:45', 'duration' => $duration]), $reference);
    verifyService(strtotime($timed['end']['dateTime']) - strtotime($timed['start']['dateTime']) === (int) $duration * 60, 'Selected duration computes end correctly across midnight');
}
try { EmailMeeting::payload($original, $values + ['duration' => '999'], $reference); throw new LogicException('Invalid duration accepted'); }
catch (InvalidArgumentException $e) {}
verifyService($payload['start']['dateTime'] === $day . 'T09:00:00-03:00' && $payload['end']['timeZone'] === 'America/Sao_Paulo', 'Brasilia times sent with UTC offset');
verifyService(count($payload['attendees']) === 2 && $payload['conferenceData']['createRequest']['conferenceSolutionKey']['type'] === 'hangoutsMeet', 'Meet optional and guests deduplicated');
verifyService($payload['source']['url'] === $reference && str_contains($payload['description'], $reference) && !str_contains($payload['description'], '<img'), 'Event retains safe email reference and context');
$withoutMeet = EmailMeeting::payload($original, array_replace($values, ['meet' => '', 'guests' => '']), $reference);
verifyService(!isset($withoutMeet['conferenceData'], $withoutMeet['attendees']), 'Meeting without Meet or guests');
foreach ([['start' => 'invalid'], ['end' => $day . 'T08:00'], ['guests' => 'not-an-email'], ['title' => ''], ['start' => '2020-01-01T09:00']] as $invalid) {
    try { EmailMeeting::payload($original, array_replace($values, $invalid), $reference); throw new LogicException('Invalid meeting accepted'); }
    catch (InvalidArgumentException $e) {}
}
$meetings = new EmailMeetingModel($db);
$key = bin2hex(random_bytes(16));
$pending = $meetings->pending($actor, $original, $service, $key, $payload);
verifyService($meetings->forUser($actor) === [], 'Unconfirmed meeting not displayed as created');
$same = $meetings->pending($actor, $original, $service, $key, EmailMeeting::payload($original, $values, $reference));
verifyService($pending['id'] === $same['id'] && $pending['google_event_id'] === $same['google_event_id'], 'Retry reuses durable event and conference request IDs');
try { $meetings->pending($actor, $original, $service, $key, $withoutMeet); throw new LogicException('Retry silently changed meeting data'); }
catch (InvalidArgumentException $e) {}
try { $meetings->pending($other, $original, $service, $key, $payload); throw new LogicException('Foreign email accepted'); }
catch (CodeIgniter\Exceptions\PageNotFoundException $e) {}
$payload = json_decode($pending['payload'], true);
$event = $payload + ['htmlLink' => 'https://www.google.com/calendar/event?eid=test', 'hangoutLink' => 'https://meet.google.com/abc-defg-hij', 'status' => 'confirmed'];
$http = new class {
    public array $queue = [];
    public array $calls = [];
    public function get($url, $options) { return $this->reply('GET', $url, $options); }
    public function post($url, $options) { return $this->reply('POST', $url, $options); }
    private function reply($method, $url, $options) {
        $this->calls[] = [$method, $url, $options];
        [$code, $body] = array_shift($this->queue);
        return new class($code, $body) {
            public function __construct(private int $code, private array $body) {}
            public function getStatusCode() { return $this->code; }
            public function getBody() { return json_encode($this->body); }
        };
    }
};
Config\Services::injectMock('curlrequest', $http);
$client = new GoogleCalendarClient();
$http->queue = [[404, []], [201, $event]];
$created = $client->createMeeting($service, $payload);
$last = end($http->calls);
verifyService($last[0] === 'POST' && str_ends_with($last[1], '/primary/events') && $last[2]['query'] === ['conferenceDataVersion' => 1, 'sendUpdates' => 'all'], 'Google API insertion enables Meet and invitations');
verifyService($last[2]['headers']['Authorization'] === 'Bearer test-access' && $last[2]['json']['id'] === $pending['google_event_id'], 'OAuth Bearer and stable event ID used');
$http->calls = []; $http->queue = [[200, $event]];
$client->createMeeting($service, $payload);
verifyService(count($http->calls) === 1 && $http->calls[0][0] === 'GET', 'Retry recovers existing event without duplicate insertion');
$http->queue = [[404, []], [409, []], [200, $event]];
verifyService($client->createMeeting($service, $payload)['id'] === $payload['id'], 'Concurrent insertion conflict recovers same event');
try { $client->createMeeting(array_replace($service, ['can_write' => false]), $payload); throw new LogicException('Read-only grant accepted'); }
catch (GoogleCalendarException $e) { verifyService(str_contains($e->getMessage(), 'Conecte novamente'), 'Old grant requests reauthorization'); }
$http->queue = [[403, ['error' => ['message' => 'secret-response']]]];
try { $client->createMeeting($service, $payload); throw new LogicException('Google refusal accepted'); }
catch (GoogleCalendarException $e) { verifyService(!str_contains($e->getMessage(), 'secret-response'), 'Google error sanitized'); }
$meetings->confirm($actor, $pending, $created);
$history = $meetings->forUser($actor);
verifyService(count($history) === 1 && $history[0]['meet_url'] === 'https://meet.google.com/abc-defg-hij' && $meetings->forUser($other) === [], 'Confirmed email association is private');
verifyService(EmailMeeting::safeUrl('javascript:alert(1)', 'meet.google.com') === '' && EmailMeeting::safeUrl('https://evil.test/', 'www.google.com') === '', 'External event links restricted');
$db->query('CREATE TABLE test_subjects (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id VARCHAR(191), name VARCHAR(150))');
require APPPATH . 'Database/Migrations/2026-09-30-000008_CreateServiceGoogleSchedule.php';
(new App\Database\Migrations\CreateServiceGoogleSchedule(Config\Database::forge($db)))->up();
require APPPATH . 'Database/Migrations/2026-10-27-000001_AddScheduleLinks.php';
(new App\Database\Migrations\AddScheduleLinks(Config\Database::forge($db)))->up();
$schedule = new App\Models\GoogleScheduleModel($db);
$normalized = GoogleCalendarClient::normalize($created, 'America/Sao_Paulo');
$schedule->storeCreated($service, $normalized);
$schedule->storeCreated($service, $normalized);
verifyService(count($schedule->upcomingForUser($actor['id'])->findAll()) === 1, 'Created event immediately appears in local agenda without duplication');
$html = view('email/account', ['account' => $emails->findOwned($accountId, $actor), 'messages' => [$original], 'calendarActive' => true, 'meetings' => $history]);
verifyService(str_contains($html, 'Agendar reunião') && str_contains($html, 'Reunião vinculada') && str_contains($html, 'Entrar no Meet') && str_contains($html, 'Reunião &lt;teste&gt;'), 'Inbox displays safe meeting details and creation button');
$form = view('email/meeting', ['message' => $original, 'calendar' => ['connected' => true, 'can_write' => false], 'requestKey' => $key]);
verifyService(str_contains($form, '<fieldset disabled>') && str_contains($form, 'autorize a criação'), 'Read-only connection shows reauthorization guidance');
echo "Email meetings: validation, ownership, API insertion, Meet, invitations, retry recovery, local agenda and views passed (no real events created).\n";
