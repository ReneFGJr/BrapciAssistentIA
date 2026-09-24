<?php
// Run with: php tests/person_lookup.php
define('ENVIRONMENT', 'testing');
define('FCPATH', dirname(__DIR__) . '/public/');
require dirname(__DIR__) . '/app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
Config\Services::injectMock('request', Config\Services::incomingrequest(null, false));

set_exception_handler(static function (Throwable $error): void {
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
});
use App\Libraries\BrapciUserLookup;
$db = Config\Database::connect(['DBDriver' => 'SQLite3', 'database' => ':memory:', 'DBDebug' => true], false);
$db->query("ATTACH DATABASE ':memory:' AS brapci");
$db->query('CREATE TABLE brapci.users (id_us INTEGER PRIMARY KEY, us_nome TEXT, us_email TEXT, us_institution TEXT, us_affiliation TEXT)');
$db->table('brapci.users')->insertBatch([
    ['id_us' => 1, 'us_nome' => 'Ana Silva', 'us_email' => 'ana@example.test', 'us_institution' => 'Universidade A', 'us_affiliation' => 'Grupo A'],
    ['id_us' => 2, 'us_nome' => 'Ana Souza', 'us_email' => 'souza@example.test', 'us_institution' => '', 'us_affiliation' => 'Grupo B'],
    ['id_us' => 3, 'us_nome' => 'Bruno', 'us_email' => 'bruno@example.test', 'us_institution' => '', 'us_affiliation' => ''],
]);
$lookup = new BrapciUserLookup($db);
$checks = 0;
function checkLookup(bool $ok, string $message): void {
    global $checks;
    if (! $ok) { throw new RuntimeException($message); }
    $checks++;
}
checkLookup($lookup->search('Ana')['total'] === 2, 'Multiple name matches');
checkLookup($lookup->search('ANA@EXAMPLE.TEST')['total'] === 1, 'Exact email search ignores case');
checkLookup($lookup->search('Not registered')['total'] === 0, 'No matching name');
checkLookup($lookup->search("x' OR 1=1 --")['total'] === 0, 'SQL treated as literal');
checkLookup($lookup->search('%')['total'] === 0, 'Wildcard escaped');
checkLookup($lookup->findMatch('Ana', '3') === null, 'Selection must belong to search');
$user = $lookup->findMatch('Ana', '1');
checkLookup($user !== null && BrapciUserLookup::prefill($user)['institution'] === 'Universidade A', 'Selected data mapped');
checkLookup(BrapciUserLookup::prefill($lookup->findMatch('Ana', '2'))['institution'] === 'Grupo B', 'Affiliation fallback');
checkLookup(BrapciUserLookup::fromQuery('new@example.test') === ['email_1' => 'new@example.test'], 'Unmatched email transferred');
checkLookup(BrapciUserLookup::fromQuery('Maria')['full_name'] === 'Maria', 'Unmatched name transferred');
$html = view('person/form', ['person' => null, 'prefill' => BrapciUserLookup::prefill($user)]);
checkLookup(str_contains($html, 'value="Ana Silva"') && str_contains($html, 'value="ana@example.test"'), 'Prefilled form renders');
checkLookup(str_contains($html, 'action="' . site_url('person') . '"'), 'Prefilled form creates rather than updates');
$html = view('person/lookup', ['query' => 'Ana', 'result' => $lookup->search('Ana'), 'error' => null]);
checkLookup(str_contains($html, 'Ana Silva') && str_contains($html, 'Ana Souza') && str_contains($html, 'Selecionar'), 'Selection panel renders matches');
$html = view('person/lookup', ['query' => '"><script>alert(1)</script>', 'result' => null, 'error' => null]);
checkLookup(! str_contains($html, '<script>alert(1)</script>'), 'Query escaped');
for ($i = 4; $i <= 25; $i++) {
    $db->table('brapci.users')->insert(['id_us' => $i, 'us_nome' => 'Ana ' . $i, 'us_email' => 'ana' . $i . '@example.test']);
}
$page = $lookup->search('Ana', 2);
checkLookup($page['total'] === 24 && count($page['users']) === 4 && $page['pages'] === 2, 'Multiple results paginated');
checkLookup($lookup->search('Ana', 999)['page'] === 2, 'Page bounded to valid range');
$db->close();
echo "Passed {$checks} person lookup checks.\n";