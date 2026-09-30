<?php
namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Exceptions\PageNotFoundException;
use InvalidArgumentException;
use RuntimeException;

class PrivateGoogleServiceModel extends Model
{
    public const SERVICE = 'usergoogleSchedule';
    protected $table = 'user_services';
    protected $returnType = 'array';

    private function owner(array $actor): string
    {
        $id = (string) ($actor['id'] ?? '');
        if ($id === '' || strlen($id) > 191) throw PageNotFoundException::forPageNotFound();
        return $id;
    }

    // Credentials are server-only; pass summary() to views.
    public function connection(array $actor): ?array
    {
        $row = $this->where('user_id', $this->owner($actor))->where('service', self::SERVICE)->first();
        if ($row === null) return null;
        if ((string) env('encryption.key', '') === '') throw new RuntimeException('Criptografia indisponível.');
        $cipher = base64_decode($row['value'], true);
        if ($cipher === false) throw new RuntimeException('Configuração inválida.');
        $data = json_decode(service('encrypter')->decrypt($cipher), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data) || !isset($data['client_id'], $data['client_secret'], $data['email'])) {
            throw new RuntimeException('Configuração inválida.');
        }
        return ['id' => (int) $row['id'], 'user_id' => $row['user_id'],
            'version' => hash('sha256', $row['value']), 'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']] + $data;
    }

    public function summary(array $actor): ?array
    {
        $data = $this->connection($actor);
        if ($data === null) return null;
        return array_intersect_key($data, array_flip(['id', 'email', 'client_id', 'created_at', 'updated_at']))
            + ['connected' => !empty($data['refresh_token']), 'has_secret' => $data['client_secret'] !== ''];
    }

    private function encrypt(array $data): string
    {
        if ((string) env('encryption.key', '') === '') throw new RuntimeException('Criptografia indisponível.');
        return base64_encode(service('encrypter')->encrypt(json_encode($data, JSON_THROW_ON_ERROR)));
    }

    public function saveConfiguration(array $actor, string $email, string $clientId, string $secret): void
    {
        $owner = $this->owner($actor);
        $email = trim($email);
        $clientId = trim($clientId);
        $secret = trim($secret);
        if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Informe um e-mail válido.');
        }
        if (strlen($clientId) > 255 || !preg_match('/^[A-Za-z0-9_-]+\.apps\.googleusercontent\.com$/D', $clientId)) {
            throw new InvalidArgumentException('Informe o Client ID OAuth do tipo Aplicativo da Web.');
        }
        $old = $this->connection($actor);
        if ($secret === '' && $old !== null && $old['client_id'] === $clientId) $secret = $old['client_secret'];
        if ($secret === '' || strlen($secret) > 512 || preg_match('/\s/', $secret)) {
            throw new InvalidArgumentException('Informe o Client Secret do Google.');
        }
        $data = ['email' => $email, 'client_id' => $clientId, 'client_secret' => $secret];
        if ($old !== null && $old['email'] === $email && $old['client_id'] === $clientId && $old['client_secret'] === $secret) {
            $data += array_intersect_key($old, array_flip(['access_token', 'refresh_token', 'expires_at']));
        }
        $now = gmdate('Y-m-d H:i:s');
        $builder = $this->db->table($this->table);
        $saved = $old === null
            ? $builder->insert(['user_id' => $owner, 'service' => self::SERVICE, 'value' => $this->encrypt($data), 'created_at' => $now, 'updated_at' => $now])
            : $builder->where(['id' => $old['id'], 'user_id' => $owner, 'service' => self::SERVICE])
                ->update(['value' => $this->encrypt($data), 'updated_at' => $now]);
        if (!$saved) throw new RuntimeException('Não foi possível salvar a configuração.');
    }

    public function saveTokens(array $connection, array $tokens): void
    {
        $current = $this->connection(['id' => $connection['user_id']]);
        if ($current === null || $current['id'] !== $connection['id'] || !hash_equals($current['version'], $connection['version'])) {
            throw new RuntimeException('A configuração mudou. Conecte novamente.');
        }
        if (!is_string($tokens['access_token'] ?? null) || $tokens['access_token'] === '' || (int) ($tokens['expires_in'] ?? 0) <= 0) {
            throw new RuntimeException('Resposta OAuth inválida.');
        }
        $data = array_intersect_key($current, array_flip(['email', 'client_id', 'client_secret', 'refresh_token']));
        $data['access_token'] = $tokens['access_token'];
        $data['expires_at'] = time() + (int) $tokens['expires_in'];
        if (!empty($tokens['refresh_token']) && is_string($tokens['refresh_token'])) $data['refresh_token'] = $tokens['refresh_token'];
        if (empty($data['refresh_token'])) throw new RuntimeException('Autorize o acesso offline conectando novamente.');
        if (!$this->db->table($this->table)->where(['id' => $current['id'], 'user_id' => $current['user_id'], 'service' => self::SERVICE])
            ->update(['value' => $this->encrypt($data), 'updated_at' => gmdate('Y-m-d H:i:s')])) {
            throw new RuntimeException('Não foi possível salvar a autorização.');
        }
    }

    public function deleteFor(array $actor): void
    {
        if (!$this->db->table($this->table)->where(['user_id' => $this->owner($actor), 'service' => self::SERVICE])->delete()) {
            throw new RuntimeException('Não foi possível excluir o serviço.');
        }
    }
}
