<?php
// Run with: php tests/person_access.php (isolated in-memory SQLite database).
define('ENVIRONMENT', 'testing');
define('FCPATH', dirname(__DIR__) . '/public/');
require dirname(__DIR__) . '/app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
Config\Services::injectMock('request', Config\Services::incomingrequest(null, false));

use App\Libraries\PersonAccessService;
use App\Models\PersonModel;
use CodeIgniter\Exceptions\PageNotFoundException;

$db = Config\Database::connect([
    'DBDriver' => 'SQLite3', 'database' => ':memory:', 'DBPrefix' => 'test_',
    'DBDebug' => true, 'foreignKeys' => true,
], false);
require APPPATH . 'Database/Migrations/2026-09-24-000001_CreatePersons.php';
require APPPATH . 'Database/Migrations/2026-09-24-000002_CreatePersonsUser.php';
$forge = Config\Database::forge($db);
(new App\Database\Migrations\CreatePersons($forge))->up();
(new App\Database\Migrations\CreatePersonsUser($forge))->up();
$service = new PersonAccessService($db);
$checks = 0;
function check(bool $condition, string $message): void {
    global $checks;
    if (! $condition) { throw new RuntimeException($message); }
    $checks++;
}
function denied(callable $operation): void {
    try { $operation(); } catch (PageNotFoundException $e) { check(true, 'Access denied'); return; }
    throw new RuntimeException('Unauthorized operation was allowed');
}
function invalid(callable $operation): void {
    try { $operation(); } catch (InvalidArgumentException $e) { check(true, 'Invalid input denied'); return; }
    throw new RuntimeException('Invalid input was accepted');
}
$owner = ['id' => 'owner', 'email' => 'owner@example.test'];
$reader = ['id' => 'reader', 'email' => 'reader@example.test'];
$editor = ['id' => 'editor', 'email' => 'editor@example.test'];
$stranger = ['id' => 'stranger', 'email' => 'stranger@example.test'];
$person = $service->create(['nickname' => 'Ana', 'full_name' => 'Ana Silva'], $owner);
$other = $service->create(['nickname' => 'Bruno', 'full_name' => 'Bruno Silva'], $stranger);
$grant = $service->access($person, $owner, true, true);
check($grant['user'] === 'owner' && $grant['user_own'] === 'owner', 'Owner linked');
check($grant['access_level'] === 'edit' && $grant['expires_at'] === null, 'Owner has permanent edit');
check((new PersonModel($db))->find($person)['cpf'] === null, 'CPF optional');
denied(fn () => $service->access($person, $stranger));
$visible = (new PersonModel($db))->whereIn('id', $service->accessibleIds($owner))->findAll();
check(count($visible) === 1 && (int) $visible[0]['id'] === $person, 'Listing excludes unshared people');
$service->share($person, $owner, ' READER@example.test ', 'read', '');
check($service->access($person, $reader)['user'] === 'reader', 'Email share binds to authenticated ID');
denied(fn () => $service->update($person, ['nickname' => 'Blocked'], $reader));
denied(fn () => $service->share($person, $reader, 'x@example.test', 'edit', ''));
$service->share($person, $owner, 'editor@example.test', 'edit', '');
check($service->update($person, ['nickname' => 'Ana updated'], $editor), 'Editor can update');
denied(fn () => $service->share($person, $editor, 'x@example.test', 'edit', ''));
denied(fn () => $service->update($other, ['nickname' => 'Blocked'], $editor));
$editor['email'] = 'changed@example.test';
check($service->access($person, $editor)['access_level'] === 'edit', 'Bound user retains access after email change');
$service->share($person, $owner, 'reader@example.test', 'edit', '');
check($service->access($person, $reader, true)['access_level'] === 'edit', 'Existing share can be upgraded');
check(count($service->shares($person, $owner)) === 2, 'Sharing again updates instead of duplicating');
$db->table('persons_user')->where('person_id', $person)->where('user', 'reader')
    ->update(['expires_at' => gmdate('Y-m-d H:i:s', time() - 1)]);
denied(fn () => $service->access($person, $reader));
denied(fn () => $service->update($person, ['nickname' => 'Blocked'], $reader));
check((new PersonModel($db))->whereIn('id', $service->accessibleIds($reader))->countAllResults() === 0, 'Expired share excluded from list');
$service->share($person, $owner, 'reader@example.test', 'read', '');
$readerGrant = $service->access($person, $reader);
$service->revoke($other, (int) $readerGrant['id'], $stranger);
check($service->access($person, $reader)['access_level'] === 'read', 'Cannot revoke another person grant');
denied(fn () => $service->revoke($person, (int) $readerGrant['id'], $editor));
$service->revoke($person, (int) $readerGrant['id'], $owner);
denied(fn () => $service->access($person, $reader));
$service->revoke($person, (int) $grant['id'], $owner);
check($service->access($person, $owner, true, true)['expires_at'] === null, 'Owner grant cannot be revoked');
invalid(fn () => $service->share($person, $owner, 'OWNER@example.test', 'read', ''));
invalid(fn () => $service->share($person, $owner, 'bad', 'read', ''));
invalid(fn () => $service->share($person, $owner, 'valid@example.test', 'admin', ''));
invalid(fn () => $service->share($person, $owner, 'valid@example.test', 'read', '2000-01-01T00:00'));
invalid(fn () => $service->share($person, $owner, 'valid@example.test', 'read', '2099-02-30T00:00'));
$service->share($person, $owner, 'future@example.test', 'read', '2099-01-01T12:00');
check($service->access($person, ['id' => 'future', 'email' => 'future@example.test'])['expires_at'] !== null, 'Future expiration accepted');
check((new PersonModel($db))->searchByName("x' OR 1=1 --")->countAllResults() === 0, 'Search treats SQL input as text');
$service->share($person, $owner, 'alias1@example.test', 'read', '');
$aliasUser = ['id' => 'alias-user', 'email' => 'alias1@example.test'];
$service->access($person, $aliasUser);
$service->share($person, $owner, 'alias2@example.test', 'edit', '');
$aliasUser['email'] = 'alias2@example.test';
check($service->access($person, $aliasUser, true)['access_level'] === 'edit', 'Multiple emails on same account do not conflict');
$viewPerson = (new PersonModel($db))->find($person);
$viewPerson['nickname'] = '<script>alert(1)</script>';
$html = view('person/show', ['person' => $viewPerson, 'canEdit' => false, 'isOwner' => false, 'shares' => []]);
check(! str_contains($html, '<script>alert(1)</script>'), 'Person values escaped');
check(! str_contains($html, 'Editar cadastro') && ! str_contains($html, 'Compartilhar cadastro'), 'Reader sees no edit or sharing controls');
$html = view('person/show', ['person' => $viewPerson, 'canEdit' => true, 'isOwner' => true, 'shares' => $service->shares($person, $owner)]);
check(str_contains($html, 'Compartilhar cadastro') && str_contains($html, 'Editar cadastro'), 'Owner sees edit and sharing controls');
check(str_contains(view('person/form', ['person' => null]), 'Nova pessoa'), 'Creation form renders');
check(str_contains(view('person/form', ['person' => $viewPerson]), 'Editar pessoa'), 'Edit form renders');
$before = $db->table('persons')->countAllResults();
$db->query("CREATE TRIGGER fail_owner BEFORE INSERT ON test_persons_user BEGIN SELECT RAISE(ABORT, 'test owner failure'); END");
try {
    $service->create(['nickname' => 'Rollback', 'full_name' => 'Rollback Test'], $owner);
    throw new LogicException('Creation unexpectedly succeeded');
} catch (RuntimeException $e) {
    check($db->table('persons')->countAllResults() === $before, 'Ownership failure rolls back person creation');
}
$db->close();
echo "Passed {$checks} person access checks.\n";