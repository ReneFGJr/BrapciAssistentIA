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
    fwrite(STDERR, 'User services test failed: ' . get_class($error) . ' at line ' . $error->getLine() . PHP_EOL);
    exit(1);
});
$db = Config\Database::connect(['DBDriver' => 'SQLite3', 'database' => ':memory:', 'DBPrefix' => 'test_', 'DBDebug' => true], false);
require APPPATH . 'Database/Migrations/2026-09-30-000007_CreateUserServices.php';
(new App\Database\Migrations\CreateUserServices(Config\Database::forge($db)))->up();
function verifyService(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$model = new App\Models\UserServiceModel($db);
$actor = ['id' => 'owner'];
$other = ['id' => 'other'];
$key = 'AIza_TestKey_ForTestingOnly_123456789';
$model->saveGoogle($actor, 'owner@example.test', $key);
$row = $db->table('user_services')->get()->getRowArray();
verifyService($row['service'] === 'googleSchedule' && $row['user_id'] === 'owner', 'Service and user');
verifyService(!str_contains($row['value'], $key) && !str_contains($row['value'], 'owner@example.test'), 'No plaintext');
$payload = json_decode(service('encrypter')->decrypt(base64_decode($row['value'])), true);
verifyService($payload === ['email' => 'owner@example.test', 'api_key' => $key], 'Encrypted payload round trip');
$summary = $model->googleSummary($actor);
verifyService($summary['has_key'] && !array_key_exists('api_key', $summary), 'Summary does not expose key');
verifyService($model->googleSummary($other) === null, 'Private settings');
$db->table('user_services')->where('id', $row['id'])->update(['created_at' => '2020-01-01 00:00:00', 'updated_at' => '2020-01-01 00:00:00']);
$model->saveGoogle($actor, 'updated@example.test', '');
$row = $db->table('user_services')->where('user_id', 'owner')->get()->getRowArray();
$payload = json_decode(service('encrypter')->decrypt(base64_decode($row['value'])), true);
verifyService($payload['api_key'] === $key && $payload['email'] === 'updated@example.test', 'Blank key preserves existing');
verifyService($row['created_at'] === '2020-01-01 00:00:00' && $row['updated_at'] !== $row['created_at'], 'Timestamps');
verifyService($db->table('user_services')->countAllResults() === 1, 'Update does not duplicate service');
$model->saveGoogle($actor, 'updated@example.test', 'Replacement_Test_Key');
$model->saveGoogle($other, 'other@example.test', 'Other_Test_Key');
verifyService($db->table('user_services')->countAllResults() === 2, 'Independent users');
foreach ([['new', 'bad-email', $key], ['new', 'new@example.test', ''], ['new', 'new@example.test', 'invalid key']] as [$user, $email, $invalidKey]) {
    try {
        $model->saveGoogle(['id' => $user], $email, $invalidKey);
        throw new RuntimeException('Invalid input accepted');
    } catch (InvalidArgumentException $exception) {}
}
try {
    $model->googleSummary([]);
    throw new RuntimeException('Anonymous access accepted');
} catch (CodeIgniter\Exceptions\PageNotFoundException $exception) {}
$registered = $model->registeredFor($actor);
verifyService(count($registered) === 1 && $registered[0]['service'] === 'googleSchedule', 'Only current user services');
verifyService(!array_key_exists('value', $registered[0]) && !array_key_exists('api_key', $registered[0]), 'Listing excludes secrets');
verifyService($model->registeredFor(['id' => 'new']) === [], 'Empty services listing');
$html = view('tools/google_schedule', ['configuration' => $model->googleSummary($actor), 'registeredServices' => $registered]);
verifyService(str_contains($html, 'Google Agenda (googleSchedule)') && str_contains($html, 'Serviços cadastrados'), 'Registered services visible');
verifyService(!str_contains($html, 'Replacement_Test_Key') && str_contains($html, 'type="password"'), 'No saved secret in HTML');
verifyService(str_contains($html, 'OAuth 2.0') && str_contains($html, 'csrf'), 'Tutorial and CSRF');
$db->table('user_services')->where('user_id', 'owner')->update(['value' => 'corrupted']);
try {
    $model->googleSummary($actor);
    throw new LogicException('Corrupted ciphertext accepted');
} catch (LogicException $exception) {
    throw $exception;
} catch (Throwable $exception) {}
verifyService(str_contains($html, 'bi-trash') && str_contains($html, '/delete'), 'Delete icon and form');
$serviceId = (int) $registered[0]['id'];
verifyService(!$model->deleteFor($serviceId, $other), 'Cannot delete another users service');
verifyService(count($model->registeredFor($actor)) === 1, 'Unauthorized delete preserves service');
verifyService($model->deleteFor($serviceId, $actor), 'Owner can delete even corrupted credentials');
verifyService($model->registeredFor($actor) === [] && $model->googleSummary($actor) === null, 'Deleted service removed');
verifyService(count($model->registeredFor($other)) === 1, 'Other users service retained');
verifyService(!$model->deleteFor($serviceId, $actor), 'Missing service returns false');
ob_end_clean();
echo "User services: encryption, isolation, validation, update, timestamps and secret masking passed.\n";
