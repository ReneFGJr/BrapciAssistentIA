<?php
namespace App\Libraries;

use App\Models\PrivateGoogleServiceModel;

class GoogleCalendarOAuth
{
    public function __construct(private ?PrivateGoogleServiceModel $model = null) {}

    public const READ_SCOPE = 'https://www.googleapis.com/auth/calendar.events.readonly';
    public const SCOPE = 'https://www.googleapis.com/auth/calendar.events';

    public static function canWrite(string $scope): bool
    {
        return array_intersect(explode(' ', $scope), [self::SCOPE, 'https://www.googleapis.com/auth/calendar']) !== [];
    }

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
        } catch (\Throwable $exception) {
            log_message('warning', 'Google OAuth token transport failure: {type}', ['type' => $exception::class]);
            throw new GoogleCalendarException('O servidor não conseguiu acessar o Google OAuth. Verifique a conexão de saída HTTPS e os certificados SSL do servidor.');
        }
        $body = json_decode($response->getBody(), true);
        if ($response->getStatusCode() !== 200) {
            $error = is_array($body) && is_string($body['error'] ?? null) ? $body['error'] : 'invalid_response';
            $grant = is_string($data['grant_type'] ?? null) ? $data['grant_type'] : '';
            log_message('notice', 'Google OAuth token rejected: status={status} error={error} grant={grant}', [
                'status' => $response->getStatusCode(), 'error' => $error, 'grant' => $grant,
            ]);
            if ($error === 'invalid_client') {
                throw new GoogleCalendarException('O Google recusou o Client ID ou o Client Secret. Confira as credenciais OAuth do tipo Aplicativo da Web.');
            }
            if ($error === 'redirect_uri_mismatch') {
                throw new GoogleCalendarException('A URL de retorno não coincide com a cadastrada no Google Cloud. Copie exatamente o URI exibido na configuração da agenda.');
            }
            if ($error === 'invalid_grant') {
                throw new GoogleCalendarException($grant === 'refresh_token'
                    ? 'A autorização do Google expirou ou foi revogada. Use Conectar com Google novamente.'
                    : 'O código de autorização expirou ou a URL de retorno diverge da configuração do Google Cloud. Inicie a conexão novamente.');
            }
            throw new GoogleCalendarException('O Google recusou a autorização OAuth (' . $error . '). Revise a configuração e tente novamente.');
        }
        if (!is_array($body) || !is_string($body['access_token'] ?? null) || $body['access_token'] === '') {
            throw new GoogleCalendarException('O Google retornou uma resposta de autorização inválida. Tente conectar novamente.');
        }
        if (isset($body['scope']) && !self::canWrite($body['scope']) && !in_array(self::READ_SCOPE, explode(' ', $body['scope']), true)) {
            throw new GoogleCalendarException('A permissão de leitura do Google Agenda não foi concedida. Conecte novamente e aceite essa permissão.');
        }
        return $body;
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
            if (isset($tokens['scope'])) $connection['scope'] = $tokens['scope'];
        }
        // Always query the primary calendar of the account that actually authorized access.
        return ['id' => $connection['id'], 'user_id' => $connection['user_id'],
            'email' => 'primary', 'access_token' => $connection['access_token'],
            'can_write' => self::canWrite((string) ($connection['scope'] ?? ''))];
    }
}
