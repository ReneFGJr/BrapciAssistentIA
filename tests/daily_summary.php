<?php
ob_start();
define('ENVIRONMENT', 'testing');
define('FCPATH', dirname(__DIR__) . '/public/');
require dirname(__DIR__) . '/app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
CodeIgniter\Boot::bootConsole($paths);
Config\Services::injectMock('request', Config\Services::incomingrequest(null, false));
set_exception_handler(static function (Throwable $error): void { fwrite(STDERR, $error . PHP_EOL); exit(1); });
function checkDaily(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$db = Config\Database::connect(['DBDriver' => 'SQLite3', 'database' => ':memory:', 'DBPrefix' => '', 'DBDebug' => true], false);
$db->query('CREATE TABLE service_google_schedule (id INTEGER PRIMARY KEY, user_id TEXT, starts_at TEXT, ends_at TEXT, status TEXT, title TEXT)');
$db->query('CREATE TABLE notes (id INTEGER PRIMARY KEY, user_id TEXT, person_id INTEGER, meeting_date TEXT, meeting_time TEXT, title TEXT, status TEXT)');
$db->query('CREATE TABLE persons (id INTEGER PRIMARY KEY, full_name TEXT)');
$db->query('CREATE TABLE persons_user (person_id INTEGER, user TEXT, access_level TEXT, expires_at TEXT)');
$db->query('CREATE TABLE kanban_items (id INTEGER PRIMARY KEY, user_id TEXT, title TEXT, status TEXT, updated_at TEXT)');
foreach ([['owner', '2026-10-22 03:00:00', '2026-10-22 04:00:00', 'confirmed'], ['other', '2026-10-22 12:00:00', '2026-10-22 13:00:00', 'confirmed'], ['owner', '2026-10-23 03:00:00', '2026-10-23 04:00:00', 'confirmed'], ['owner', '2026-10-22 12:00:00', '2026-10-22 13:00:00', 'cancelled']] as $row) {
    $db->table('service_google_schedule')->insert(array_combine(['user_id', 'starts_at', 'ends_at', 'status'], $row) + ['title' => '<script>bad</script>']);
}
foreach (['owner', 'other'] as $user) {
    $db->table('notes')->insert(['user_id' => $user, 'meeting_date' => '2026-10-22', 'meeting_time' => '10:00', 'title' => 'Meeting', 'status' => 'scheduled']);
    $db->table('kanban_items')->insert(['user_id' => $user, 'title' => 'Task', 'status' => 'todo', 'updated_at' => '2026-10-22 12:00:00']);
}
$summary = (new App\Libraries\DailySummary($db))->forUser(['id' => 'owner'], new DateTimeImmutable('2026-10-23 01:00:00 UTC'));
checkDaily($summary['today']->format('Y-m-d') === '2026-10-22', 'Local day around UTC midnight');
foreach (['events', 'notes', 'tasks'] as $key) checkDaily(count($summary[$key]) === 1, 'Date and ownership: ' . $key);
$html = view('home/daily', $summary);
checkDaily(!str_contains($html, '<script>bad</script>') && str_contains($html, '&lt;script&gt;'), 'Summary escapes content');
$filter = new App\Filters\AuthFilter();
$request = Config\Services::incomingrequest();
foreach ([[], ['logged_in' => true], ['logged_in' => true, 'auth_user' => ['id' => '']], ['logged_in' => 'yes', 'auth_user' => ['id' => 'owner']]] as $state) {
    session()->remove(['logged_in', 'auth_user']);
    session()->set($state);
    checkDaily($filter->before($request) instanceof CodeIgniter\HTTP\RedirectResponse, 'Invalid session denied');
}
session()->set(['logged_in' => true, 'auth_user' => ['id' => 'owner']]);
checkDaily($filter->before($request) === null, 'Authenticated session accepted');
config('Session')->expiration = 60;
session()->set('auth_last_activity', time() - 30);
$lastActivity = session('auth_last_activity');
checkDaily(\App\Libraries\AuthSession::valid(), 'Active session valid');
checkDaily(session('auth_last_activity') === $lastActivity, 'Status check does not renew session');
session()->set('auth_last_activity', time() - 61);
$denied = $filter->before($request);
checkDaily($denied instanceof CodeIgniter\HTTP\RedirectResponse, 'Expired session denied');
checkDaily($denied->getHeaderLine('Location') === base_url('/'), 'Redirect uses application root');
checkDaily(session('auth_user') === null, 'Expired authentication removed');
session()->set(['logged_in' => true, 'auth_user' => ['id' => 'owner'], 'auth_last_activity' => time() - 61]);
$guard = new \App\Filters\SessionExpiryFilter();
checkDaily($guard->before($request) instanceof CodeIgniter\HTTP\RedirectResponse, 'Expired session redirects on other pages');
session()->set(['logged_in' => true, 'auth_user' => ['id' => 'owner'], 'auth_last_activity' => time() - 20]);
checkDaily($guard->before($request) === null && session('auth_last_activity') >= time() - 1, 'Normal request renews activity');
$auth = new \App\Controllers\Auth();
$auth->initController($request, Config\Services::response(), Config\Services::logger());
checkDaily($auth->status()->getStatusCode() === 200, 'Status accepts active login');
session()->remove(['logged_in', 'auth_user', 'auth_last_activity']);
checkDaily($auth->status()->getStatusCode() === 401, 'Status reports missing session');
echo "Daily summary: local date, ownership, cancelled events, escaping and authentication passed.\n";
