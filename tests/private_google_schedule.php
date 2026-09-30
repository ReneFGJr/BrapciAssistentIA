<?php
require __DIR__ . '/user_services.php';
set_exception_handler(static function (Throwable $e): void {
    fwrite(STDERR, 'OAuth test: ' . $e->getMessage() . PHP_EOL . $e->getTraceAsString()); exit(1);
});
use App\Models\PrivateGoogleServiceModel;
use App\Libraries\GoogleCalendarOAuth;
use App\Libraries\GoogleCalendarClient;
use App\Libraries\GoogleCalendarException;

$private = new PrivateGoogleServiceModel($db);
$private->saveConfiguration($actor, 'owner@example.test', 'test-client.apps.googleusercontent.com', 'test-secret');
$connection = $private->connection($actor);
verifyService($private->connection($other) === null, 'Private service isolated');
$row = $db->table('user_services')->where('id', $connection['id'])->get()->getRowArray();
verifyService($row['service'] === 'usergoogleSchedule' && !str_contains($row['value'], 'test-secret'), 'Separate encrypted service');
verifyService(!array_key_exists('client_secret', $private->summary($actor)), 'Secret excluded from summary');
$private->saveConfiguration($actor, 'owner@example.test', 'test-client.apps.googleusercontent.com', '');
$connection = $private->connection($actor);
verifyService($connection['client_secret'] === 'test-secret', 'Blank secret retained');
$pending = ['state' => bin2hex(random_bytes(32)), 'verifier' => bin2hex(random_bytes(32)), 'owner' => 'owner',
    'version' => $connection['version'], 'expires_at' => time() + 600, 'redirect_uri' => 'http://localhost/index.php/tools/usergoogleSchedule/callback'];
$url = GoogleCalendarOAuth::authorizationUrl($connection, $pending);
parse_str(parse_url($url, PHP_URL_QUERY), $query);
verifyService($query['scope'] === GoogleCalendarOAuth::SCOPE && $query['access_type'] === 'offline'
    && $query['code_challenge_method'] === 'S256' && !str_contains($url, 'test-secret'), 'Consent read-only with PKCE');
verifyService(GoogleCalendarOAuth::validState($pending, $pending['state'], $actor, $connection), 'Valid callback');
verifyService(!GoogleCalendarOAuth::validState($pending, 'wrong', $actor, $connection), 'Reject forged state');
verifyService(!GoogleCalendarOAuth::validState($pending, $pending['state'], $other, $connection), 'Reject different local user');
verifyService(!GoogleCalendarOAuth::validState(array_replace($pending, ['expires_at' => time()-1]), $pending['state'], $actor, $connection), 'Reject expired state');
verifyService(!GoogleCalendarOAuth::validState(null, $pending['state'], $actor, $connection), 'Reject replay without pending state');
verifyService(!GoogleCalendarOAuth::validRedirect('http://assistentia/index.php/callback')
    && GoogleCalendarOAuth::validRedirect('http://localhost/index.php/callback')
    && GoogleCalendarOAuth::validRedirect('https://example.com/callback'), 'Redirect requirements');
$http = new class {
    public array $calls = [];
    public int $code = 200;
    public array $body = [];
    public function post($url, $options) { return $this->reply('POST', $url, $options); }
    public function get($url, $options) { return $this->reply('GET', $url, $options); }
    private function reply($method, $url, $options) {
        $this->calls[] = [$method, $url, $options];
        return new class($this->code, $this->body) {
            public function __construct(private int $code, private array $body) {}
            public function getStatusCode() { return $this->code; }
            public function getBody() { return json_encode($this->body); }
        };
    }
};
Config\Services::injectMock('curlrequest', $http);
$http->body = ['access_token' => 'access-test', 'refresh_token' => 'refresh-test', 'expires_in' => 1, 'scope' => GoogleCalendarOAuth::SCOPE];
$oauth = new GoogleCalendarOAuth($private);
$tokens = $oauth->tokens(['grant_type' => 'authorization_code', 'code' => 'test-code']);
$private->saveTokens($connection, $tokens);
verifyService($private->summary($actor)['connected'], 'Authorization saved');
$stored = $db->table('user_services')->where('id', $connection['id'])->get()->getRowArray();
verifyService(!str_contains($stored['value'], 'refresh-test') && !str_contains($stored['value'], 'access-test'), 'Tokens encrypted');
$http->body = ['access_token' => 'renewed-test', 'expires_in' => 3600];
$authorized = $oauth->access($actor);
verifyService($authorized['access_token'] === 'renewed-test' && $authorized['email'] === 'primary', 'Expired access refreshed, primary calendar');
verifyService($private->connection($actor)['refresh_token'] === 'refresh-test', 'Refresh token retained');
$last = end($http->calls);
verifyService($last[2]['form_params']['grant_type'] === 'refresh_token', 'Refresh exchange used');
$http->body = ['accessRole' => 'owner', 'timeZone' => 'America/Sao_Paulo', 'items' => [[
    'id' => 'private-event', 'summary' => 'Planejamento', 'description' => '<b>Detalhes</b>', 'location' => 'Sala A',
    'start' => ['dateTime' => '2026-10-01T10:00:00-03:00'], 'end' => ['dateTime' => '2026-10-01T11:00:00-03:00'],
]]];
$events = (new GoogleCalendarClient())->fetch($authorized);
$last = end($http->calls);
verifyService($last[2]['headers']['Authorization'] === 'Bearer renewed-test' && !isset($last[2]['headers']['X-Goog-Api-Key']), 'Bearer authentication used');
verifyService(str_ends_with($last[1], '/primary/events') && $events[0]['title'] === 'Planejamento' && $events[0]['location'] === 'Sala A' && $events[0]['description'] === '<b>Detalhes</b>', 'Private event details read');
$http->code = 400;
$http->body = ['error' => 'invalid_grant', 'error_description' => 'secret must never leak'];
try { $oauth->tokens([]); throw new LogicException('Failed token request accepted'); }
catch (GoogleCalendarException $e) { verifyService(!str_contains($e->getMessage(), 'secret must never leak'), 'Sanitized OAuth errors'); }
$html = view('tools/user_google_schedule', ['configuration' => $private->summary($actor), 'redirectUri' => $pending['redirect_uri'], 'validRedirect' => true]);
verifyService(!str_contains($html, 'test-secret') && !str_contains($html, 'refresh-test') && str_contains($html, 'Conectar com Google'), 'View excludes secrets');
$stale = $private->connection($actor);
$private->saveConfiguration($actor, 'changed@example.test', 'test-client.apps.googleusercontent.com', '');
verifyService(!$private->summary($actor)['connected'], 'Configuration changes clear authorization');
verifyService(!GoogleCalendarOAuth::validState($pending, $pending['state'], $actor, $private->connection($actor)), 'Changed credentials invalidate callback');
try { $private->saveTokens($stale, $tokens); throw new LogicException('Stale tokens accepted'); }
catch (RuntimeException $e) {}
$private->deleteFor($other);
verifyService($private->connection($actor) !== null, 'Deletion isolated by user');
$private->deleteFor($actor);
verifyService($private->connection($actor) === null && $model->googleConnection($other) !== null, 'Only private service deleted');
try { $private->saveTokens($stale, $tokens); throw new LogicException('Deleted service recreated'); }
catch (RuntimeException $e) {}
echo "Private calendar: encryption, isolation, OAuth state, refresh, Bearer request and details passed.\n";
