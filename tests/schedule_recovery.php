<?php
require __DIR__ . '/google_schedule.php';

$controller = new class extends App\Controllers\Schedule {
    public function runCollection(array $services, $model, $client, array &$errors): int {
        return $this->collect($services, $model, $client, $errors);
    }
    public function due(array $services, $model, string $user): array {
        return $this->dueConnections($services, $model, $user);
    }
};
$failed = array_replace($connection, ['id' => 9001]);
$calendar->replaceSnapshot($failed, [$event]);
$beforeFailure = $calendar->upcoming($failed)->findAll();
$client = new class($event, $failed['id']) extends App\Libraries\GoogleCalendarClient {
    public array $calls = [];
    public function __construct(private array $event, private int $failedId) {}
    public function fetch(array $credentials): array {
        $this->calls[] = $credentials['id'];
        if ($credentials['id'] === $this->failedId) throw new App\Libraries\GoogleCalendarException('Conecte novamente a agenda particular.');
        return [array_replace($this->event, ['title' => 'Evento atualizado após falha de outra agenda'])];
    }
};
$errors = [];
verifyService($controller->runCollection([$failed, $connection], $calendar, $client, $errors) === 1, 'Healthy calendar collected after another calendar fails');
verifyService(count($errors) === 1 && count($client->calls) === 2, 'Independent failures reported without stopping other sources');
verifyService($calendar->upcoming($failed)->findAll() === $beforeFailure, 'Failed connection keeps its saved events');
verifyService($calendar->upcoming($connection)->first()['title'] === 'Evento atualizado após falha de outra agenda', 'Successful snapshot updated');
verifyService($controller->due([$connection], $calendar, 'owner') === [], 'Fresh calendar does not trigger another request');
$db->table('service_google_schedule')->where('service_id', $connection['id'])->update(['synced_at' => gmdate('Y-m-d H:i:s', time() - 600)]);
$attemptKey = 'schedule_attempt_' . $connection['id'] . '_' . sha1($connection['email']);
session()->set($attemptKey, time() - 120);
verifyService($controller->due([$connection], $calendar, 'owner') === [$connection], 'Stale calendar is collected automatically');
session()->set($attemptKey, time());
verifyService($controller->due([$connection], $calendar, 'owner') === [], 'Recent failed attempts are throttled');
$newConnection = array_replace($connection, ['id' => 9002]);
verifyService($controller->due([$newConnection], $calendar, 'owner') === [$newConnection], 'Calendar without saved events receives initial collection');
echo "Schedule recovery: independent collection, saved data preservation, initial refresh, freshness and retry throttling passed.\n";
