<?php
namespace App\Models;
use CodeIgniter\Model;
use CodeIgniter\Exceptions\PageNotFoundException;
use InvalidArgumentException;
use RuntimeException;
class UserServiceModel extends Model
{
    public const GOOGLE_SCHEDULE = 'googleSchedule';
    protected $table = 'user_services';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'service', 'value', 'created_at', 'updated_at'];

    private function userId(array $actor): string
    {
        $id = (string) ($actor['id'] ?? '');
        if ($id === '' || strlen($id) > 191) {
            throw PageNotFoundException::forPageNotFound();
        }
        return $id;
    }
    private function credentials(array $row): array
    {
        if ((string) env('encryption.key', '') === '') {
            throw new RuntimeException('Criptografia indisponível.');
        }
        $ciphertext = base64_decode($row['value'], true);
        if ($ciphertext === false) {
            throw new RuntimeException('Configuração inválida.');
        }
        $data = json_decode(service('encrypter')->decrypt($ciphertext), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data) || !is_string($data['email'] ?? null) || !is_string($data['api_key'] ?? null)) {
            throw new RuntimeException('Configuração inválida.');
        }
        return $data;
    }
    public function deleteFor(int $id, array $actor): bool
    {
        $deleted = $this->db->table($this->table)
            ->where(['id' => $id, 'user_id' => $this->userId($actor)])->delete();
        return $deleted && $this->db->affectedRows() > 0;
    }

    public function hasGoogle(array $actor): bool
    {
        return $this->where('user_id', $this->userId($actor))
            ->where('service', self::GOOGLE_SCHEDULE)->countAllResults() > 0;
    }
    // Server-side use only. Never pass this array to a view or response.
    public function googleConnection(array $actor): ?array
    {
        $row = $this->googleRow($actor);
        if ($row === null) return null;
        $credentials = $this->credentials($row);
        if ($credentials['api_key'] === '') return null;
        return ['id' => (int) $row['id'], 'user_id' => $row['user_id']] + $credentials;
    }

    public function registeredFor(array $actor): array
    {
        return $this->select('id, service, created_at, updated_at')
            ->where('user_id', $this->userId($actor))->orderBy('service')->findAll();
    }

    private function googleRow(array $actor): ?array
    {
        return $this->where('user_id', $this->userId($actor))->where('service', self::GOOGLE_SCHEDULE)->first();
    }
    public function googleSummary(array $actor): ?array
    {
        $row = $this->googleRow($actor);
        if ($row === null) {
            return null;
        }
        $data = $this->credentials($row);
        return ['email' => $data['email'], 'has_key' => $data['api_key'] !== '',
            'created_at' => $row['created_at'], 'updated_at' => $row['updated_at']];
    }
    public function saveGoogle(array $actor, string $email, string $apiKey): void
    {
        $userId = $this->userId($actor);
        $email = trim($email);
        $apiKey = trim($apiKey);
        if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Informe um e-mail válido.');
        }
        if ($apiKey !== '' && (strlen($apiKey) > 512 || !preg_match('/^[A-Za-z0-9_-]+$/D', $apiKey))) {
            throw new InvalidArgumentException('Informe uma API key válida, sem espaços.');
        }
        $row = $this->googleRow($actor);
        if ($apiKey === '' && $row !== null) {
            $apiKey = $this->credentials($row)['api_key'];
        }
        if ($apiKey === '') {
            throw new InvalidArgumentException('Informe a API key no primeiro cadastro.');
        }
        if ((string) env('encryption.key', '') === '') {
            throw new RuntimeException('Criptografia indisponível.');
        }
        $payload = json_encode(['email' => $email, 'api_key' => $apiKey], JSON_THROW_ON_ERROR);
        $value = base64_encode(service('encrypter')->encrypt($payload));
        $now = date('Y-m-d H:i:s');
        $builder = $this->db->table($this->table);
        $saved = $row === null
            ? $builder->insert(['user_id' => $userId, 'service' => self::GOOGLE_SCHEDULE,
                'value' => $value, 'created_at' => $now, 'updated_at' => $now])
            : $builder->where(['id' => $row['id'], 'user_id' => $userId, 'service' => self::GOOGLE_SCHEDULE])
                ->update(['value' => $value, 'updated_at' => $now]);
        if (!$saved) {
            throw new RuntimeException('Não foi possível salvar a configuração.');
        }
    }
}
