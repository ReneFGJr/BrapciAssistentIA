<?php
define('ENVIRONMENT', 'testing');
define('FCPATH', dirname(__DIR__) . '/public/');
require dirname(__DIR__) . '/app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
$db = Config\Database::connect([
    'DBDriver' => 'SQLite3', 'database' => ':memory:', 'DBPrefix' => 'test_',
    'DBDebug' => true, 'foreignKeys' => true,
], false);
require APPPATH . 'Database/Migrations/2026-09-24-000001_CreatePersons.php';
require APPPATH . 'Database/Migrations/2026-09-24-000002_CreatePersonsUser.php';
$forge = Config\Database::forge($db);
(new App\Database\Migrations\CreatePersons($forge))->up();
(new App\Database\Migrations\CreatePersonsUser($forge))->up();
require APPPATH . 'Database/Migrations/2026-09-24-000005_AddPersonPhoto.php';
(new App\Database\Migrations\AddPersonPhoto($forge))->up();
function check(bool $value, string $message): void {
    if (! $value) { throw new RuntimeException($message); }
}
$downloads = 0;
$service = new App\Libraries\PersonImportService($db, static function ($url) use (&$downloads) {
    $downloads++;
    if ($url === 'https://lh3.googleusercontent.com/unavailable') {
        throw new RuntimeException('Unavailable photo');
    }
    return md5($url) . '.jpg';
});
$actor = ['id' => 'csv-owner'];
$file = tempnam(sys_get_temp_dir(), 'contacts');
try {
    file_put_contents($file, "\xEF\xBB\xBFFirst Name,Middle Name,Last Name,Nickname,E-mail 1 - Value,Phone 1 - Value,Notes\n\"João\",da,Silva,Joca,joao@example.test,51999999999,\"linha 1\nlinha 2\"\nInválido,,,,bad,,\n");
    $result = $service->import($file, $actor);
    check($result['imported'] === 1 && $result['duplicates'] === 0 && $result['invalid'] === 1, 'Import counts');
    $person = $db->table('persons')->get()->getRowArray();
    check($person['full_name'] === 'João da Silva' && $person['nickname'] === 'Joca', 'UTF-8 and name mapping');
    $grant = $db->table('persons_user')->get()->getRowArray();
    check($grant['user'] === 'csv-owner' && $grant['user_own'] === 'csv-owner' && $grant['access_level'] === 'edit', 'Owner grant');
    check($service->import($file, $actor)['duplicates'] === 1, 'Repeated import');
    check($service->import($file, ['id' => 'other-owner'])['imported'] === 1, 'Owner isolation');
    $before = $db->table('persons')->countAllResults();
    file_put_contents($file, "First Name,E-mail 1 - Value\nFirst,first@example.test\nSecond,second@example.test\n");
    $db->query("CREATE TRIGGER fail_second BEFORE INSERT ON test_persons WHEN NEW.full_name = 'Second' BEGIN SELECT RAISE(ABORT, 'test failure'); END");
    try {
        $service->import($file, $actor);
        throw new LogicException('Expected import failure');
    } catch (LogicException $e) {
        throw $e;
    } catch (Throwable $e) {
        check($db->table('persons')->countAllResults() === $before, 'Whole import rolled back');
        check($db->table('persons_user')->countAllResults() === $before, 'Grants rolled back');
    }
    $db->query('DROP TRIGGER fail_second');
    $db->resetTransStatus();
    file_put_contents($file, "First Name,E-mail 1 - Value,Phone 1 - Value,Photo\nJOÃO   DA SILVA,new@example.test,+55 (51) 99999-9999,https://lh3.googleusercontent.com/photo\n");
    $result = $service->import($file, $actor);
    check($result['imported'] === 0 && $result['photos'] === 1, 'Existing contact receives photo despite changed email/format');
    $downloadCount = $downloads;
    check($service->import($file, $actor)['photos'] === 0 && $downloads === $downloadCount, 'Existing photo is preserved');
    file_put_contents($file, "First Name,E-mail 1 - Value,Phone 1 - Value\nDifferent name,,051999999999\n");
    check($service->import($file, $actor)['imported'] === 0, 'Phone-only match prevents duplicate');
    file_put_contents($file, "First Name,E-mail 1 - Value,Photo\nNew contact,,https://lh3.googleusercontent.com/unavailable\n");
    $result = $service->import($file, $actor);
    check($result['imported'] === 1 && $result['photo_errors'] === 1, 'Photo failure does not lose contact');
    try {
        (new App\Libraries\ContactPhotoDownloader())->download('http://127.0.0.1/private');
        throw new LogicException('Unsafe photo URL accepted');
    } catch (RuntimeException $expected) {
        check(! $expected instanceof LogicException, 'Unsafe URL rejected');
    }
    $actual = $service->import(ROOTPATH . '_Documments/contacts.csv', $actor);
    check($actual['imported'] > 0, 'Real CSV parsed');
    check($service->import(ROOTPATH . '_Documments/contacts.csv', $actor)['imported'] === 0, 'Real CSV repeat safe');
    echo 'PASS: import, ownership, duplicates, rollback, UTF-8, multiline; real CSV: ' . json_encode($actual) . PHP_EOL;
} finally {
    unlink($file);
}