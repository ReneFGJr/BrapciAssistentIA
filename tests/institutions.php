<?php
// Run with: php tests/institutions.php (isolated SQLite database).
define('ENVIRONMENT', 'testing');
define('FCPATH', dirname(__DIR__) . '/public/');
require dirname(__DIR__) . '/app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
set_exception_handler(static function (Throwable $error): void {
    fwrite(STDERR, $error->getMessage() . PHP_EOL . $error->getTraceAsString() . PHP_EOL);
    exit(1);
});
$db = Config\Database::connect([
    'DBDriver' => 'SQLite3', 'database' => ':memory:', 'DBPrefix' => 'test_',
    'DBDebug' => true, 'foreignKeys' => true,
], false);
$forge = Config\Database::forge($db);
foreach (['000001_CreatePersons', '000002_CreatePersonsUser'] as $file) {
    require APPPATH . 'Database/Migrations/2026-09-24-' . $file . '.php';
}
(new App\Database\Migrations\CreatePersons($forge))->up();
(new App\Database\Migrations\CreatePersonsUser($forge))->up();
$db->table('persons')->insert(['nickname' => 'Legacy', 'full_name' => 'Legacy Test', 'institution' => 'Instituto Teste']);
$legacyId = $db->insertID();
require APPPATH . 'Database/Migrations/2026-09-24-000003_CreateInstitutions.php';
$migration = new App\Database\Migrations\CreateInstitutions($forge);
$migration->up();
$checks = 0;
function checkInstitution(bool $condition, string $message): void {
    global $checks;
    if (! $condition) { throw new RuntimeException($message); }
    $checks++;
}
$model = new App\Models\InstitutionModel($db);
$person = $db->table('persons')->where('id', $legacyId)->get()->getRowArray();
checkInstitution($person['institution_id'] !== null, 'Legacy person linked');
$institution = $model->find($person['institution_id']);
checkInstitution($institution['name'] === 'Instituto Teste' && $institution['ror_id'] === null, 'Legacy name preserved without invented ROR');
checkInstitution($institution['created_at'] !== null, 'Creation date set');
checkInstitution($model->resolveName(' Instituto Teste ') === (int) $institution['id'], 'Existing institution reused');
checkInstitution($model->resolveName('') === null, 'Empty affiliation optional');
checkInstitution($model->insert(['name' => 'Invalid latitude', 'latitude' => 91]) === false, 'Latitude bounded');
checkInstitution($model->insert(['name' => 'Invalid longitude', 'longitude' => -181]) === false, 'Longitude bounded');
checkInstitution($model->insert(['name' => 'Invalid ROR', 'ror_id' => 'https://example.org/x']) === false, 'ROR namespace validated');
$id = $model->insert([
    'name' => 'Institution Test', 'ror_id' => 'https://ror.org/00xmkp704', 'acronym' => 'IT',
    'address' => 'Test address', 'city' => 'Test City', 'state' => 'Test State',
    'country' => 'Brazil', 'country_code' => 'BR', 'latitude' => -30.03, 'longitude' => -51.23,
    'established_year' => 1990,
]);
checkInstitution($id !== false, 'Requested fields stored');
$stored = $model->find($id);
checkInstitution((float) $stored['latitude'] === -30.03 && (float) $stored['longitude'] === -51.23, 'Coordinate precision preserved');
require APPPATH . 'Database/Migrations/2026-09-24-000004_UseInstitutionReference.php';
$referenceMigration = new App\Database\Migrations\UseInstitutionReference($forge);
$referenceMigration->up();
checkInstitution(! in_array('institution', $db->getFieldNames('persons'), true), 'Text affiliation removed');
$service = new App\Libraries\PersonAccessService($db);
$owner = ['id' => 'owner', 'email' => 'owner@example.test'];
$personId = $service->create(['nickname' => 'New', 'full_name' => 'New Person', 'institution_id' => $institution['id']], $owner);
checkInstitution((int) $db->table('persons')->where('id', $personId)->get()->getRowArray()['institution_id'] === (int) $institution['id'], 'New person linked');
$otherId = $model->insert(['name' => 'Outra instituição']);
$service->update($personId, ['institution_id' => $otherId], $owner);
try {
    $service->update($personId, ['institution_id' => 999999], $owner);
    throw new RuntimeException('Invalid institution accepted');
} catch (InvalidArgumentException $exception) {
    checkInstitution(true, 'Unknown institution rejected');
}
$updated = $db->table('persons')->where('id', $personId)->get()->getRowArray();
checkInstitution($model->find($updated['institution_id'])['name'] === 'Outra instituição', 'Changed affiliation linked');
$service->update($personId, ['institution_id' => ''], $owner);
checkInstitution($db->table('persons')->where('id', $personId)->get()->getRowArray()['institution_id'] === null, 'Clearing affiliation removes link');
$db->table('institutions')->where('id', $institution['id'])->delete();
checkInstitution($db->table('persons')->where('id', $legacyId)->get()->getRowArray()['institution_id'] === null, 'Foreign key sets null on deletion');
$referenceMigration->down();
unset($db->dataCache['field_names']);
checkInstitution(in_array('institution', $db->getFieldNames('persons'), true), 'Rollback restores legacy field');
$migration->down();
checkInstitution(! $db->tableExists('institutions') && $db->table('persons')->countAllResults() === 2, 'Rollback preserves persons');
echo "Passed {$checks} institution checks.\n";