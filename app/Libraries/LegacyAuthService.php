<?php

namespace App\Libraries;

use App\Exceptions\LegacyAuthException;
use Throwable;

class LegacyAuthService
{
    private string $endpoint;
    private string $caBundle;

    public function __construct()
    {
        $this->endpoint = (string) env(
            'legacyAuth.endpoint',
            'https://cip.brapci.inf.br/api/socials/signin'
        );
        $this->caBundle = (string) env('legacyAuth.caBundle', ini_get('curl.cainfo'));

        if ($this->caBundle === '' || ! is_file($this->caBundle)) {
            throw new LegacyAuthException(
                'O certificado CA local não foi encontrado.',
                ['type' => 'ca_bundle_missing', 'configured_path' => $this->caBundle]
            );
        }
    }

    public function login(string $login, string $password): array
    {
        try {
            $response = service('curlrequest')->post($this->endpoint, [
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode(['user' => $login, 'pwd' => $password], JSON_THROW_ON_ERROR),
                'connect_timeout' => 5,
                'timeout'         => 15,
                'http_errors'     => false,
                'verify'          => $this->caBundle,
            ]);
        } catch (Throwable $exception) {
            log_message('error', 'Falha ao acessar o login legado: {message}', [
                'message' => $exception->getMessage(),
            ]);

            throw new LegacyAuthException(
                'O serviço de autenticação está indisponível. Detalhe: ' . $exception->getMessage(),
                [
                    'type'     => 'connection_error',
                    'endpoint' => $this->endpoint,
                    'message'  => $exception->getMessage(),
                ],
                $exception
            );
        }

        $body = (string) $response->getBody();
        $payload = json_decode($body, true);
        $httpStatus = $response->getStatusCode();

        if (! is_array($payload)) {
            throw new LegacyAuthException(
                'O serviço de autenticação retornou uma resposta inválida.',
                [
                    'type'         => 'invalid_json',
                    'http_status'  => $httpStatus,
                    'json_error'   => json_last_error_msg(),
                    'raw_response' => mb_substr($body, 0, 4000),
                ]
            );
        }

        $safePayload = $this->redactSensitiveData($payload);
        $legacyStatus = isset($payload['status']) ? (int) $payload['status'] : null;
        $hasUserObject = isset($payload['user']) && is_array($payload['user']);
        $isAuthenticated = $legacyStatus === 200 || $hasUserObject;

        if (! $isAuthenticated) {
            throw new LegacyAuthException(
                (string) ($payload['message'] ?? $payload['error'] ?? 'Login ou senha inválidos.'),
                [
                    'http_status'  => $httpStatus,
                    'legacy_status' => $legacyStatus,
                    'response'     => $safePayload,
                ]
            );
        }

        $user = $hasUserObject ? $payload['user'] : $payload;
        $id = $user['id'] ?? $user['ID'] ?? $user['persistent-id'] ?? $payload['id'] ?? $payload['ID'] ?? $payload['persistent-id'] ?? null;

        if ($id === null || $id === '') {
            throw new LegacyAuthException(
                'O serviço não retornou o identificador do usuário.',
                ['http_status' => $httpStatus, 'response' => $safePayload]
            );
        }

        $displayName = $user['displayName'] ?? $payload['displayName'] ?? null;
        if ($displayName === null && isset($payload['user']) && is_string($payload['user'])) {
            $displayName = $payload['user'];
        }

        return [
            'status'        => (string) ($payload['status'] ?? '200'),
            'message'       => $payload['message'] ?? null,
            'id'            => (string) $id,
            'user'          => is_string($payload['user'] ?? null) ? $payload['user'] : $displayName,
            'email'         => $user['email'] ?? $payload['email'] ?? null,
            'displayName'   => $displayName,
            'givenName'     => $user['givenName'] ?? $payload['givenName'] ?? null,
            'persistent-id' => $user['persistent-id'] ?? $payload['persistent-id'] ?? null,
            'admin'         => filter_var($user['admin'] ?? $payload['admin'] ?? false, FILTER_VALIDATE_BOOL),
            'token'         => $user['token'] ?? $payload['token'] ?? null,
        ];
    }

    private function redactSensitiveData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), ['pwd', 'password', 'pass', 'token'], true)) {
                $data[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $data[$key] = $this->redactSensitiveData($value);
            }
        }

        return $data;
    }
}
