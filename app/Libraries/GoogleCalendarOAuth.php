<?php
namespace App\Libraries;

use App\Models\PrivateGoogleServiceModel;

class GoogleCalendarOAuth
{
    public function __construct(private ?PrivateGoogleServiceModel $model = null) {}

    public const SCOPE = 'https://www.googleapis.com/auth/calendar.events.readonly';

    public static function redirectUri(): string
    {
        return site_url('tools/usergoogleSchedule/callback');
    }

    public static function validRedirect(string $uri): bool
    {
        $host = parse_url($uri, PHP_URL_HOST);
        $scheme = parse_url($uri, PHP_URL_SCHEME);
        return ($scheme === 'https' && is_string($host) && str_contains($host, '.'))
            || ($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1', '[::1]'], true));
    }

    public static function authorizationUrl(array $connection, array $pending): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $connection['client_id'], 'redirect_uri' => $pending['redirect_uri'],
            'response_type' => 'code', 'scope' => self::SCOPE, 'access_type' => 'offline',
            'prompt' => 'consent select_account', 'login_hint' => $connection['email'],
            'state' => $pending['state'], 'code_challenge_method' => 'S256',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $pending['verifier'], true)), '+/', '-_'), '='),
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public static function validState(?array $pending, $state, array $actor, array $connection): bool
    {
        return $pending !== null && is_string($state) && $state !== ''
            && isset($pending['state'], $pending['owner'], $pending['version'], $pending['expires_at'])
            && hash_equals($pending['state'], $state)
            && $pending['owner'] === (string) ($actor['id'] ?? '')
            && hash_equals($pending['version'], $connection['version'])
            && $pending['expires_at'] >= time();
    }

    public function tokens(array $data): array
    {
        try {
            $response = service('curlrequest')->post('https://oauth2.googleapis.com/token', [
                'form_params' => $data, 'connect_timeout' => 5, 'timeout' => 15,
                'http_errors' => false, 'allow_redirects' => false,
            ]);
            $body = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            if ($response->getStatusCode() !== 200 || !is_array($body) || empty($body['access_token'])) {
                throw new \RuntimeException();
            }
            if (isset($body['scope']) && !in_array(self::SCOPE, explode(' ', $body['scope']), true)) {
                throw new \RuntimeException();
            }
            return $body;
        } catch (\Throwable $exception) {
            throw new GoogleCalendarException('Não foi possível autorizar a agenda particular. Confira as credenciais e conecte novamente ao Google.');
        }
    }

    public function access(array $actor): ?array
    {
        $model = $this->model ?? new PrivateGoogleServiceModel();
        $connection = $model->connection($actor);
        if ($connection === null || empty($connection['refresh_token'])) return null;
        if (empty($connection['access_token']) || (int) ($connection['expires_at'] ?? 0) <= time() + 60) {
            $tokens = $this->tokens([
                'grant_type' => 'refresh_token', 'refresh_token' => $connection['refresh_token'],
                'client_id' => $connection['client_id'], 'client_secret' => $connection['client_secret'],
            ]);
            $model->saveTokens($connection, $tokens);
            $connection['access_token'] = $tokens['access_token'];
        }
        // Always query the primary calendar of the account that actually authorized access.
        return ['id' => $connection['id'], 'user_id' => $connection['user_id'],
            'email' => 'primary', 'access_token' => $connection['access_token']];
    }
}
