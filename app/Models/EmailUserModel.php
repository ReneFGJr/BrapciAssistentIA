<?php
namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Exceptions\PageNotFoundException;
use InvalidArgumentException;
use RuntimeException;

class EmailUserModel extends Model
{
    protected $table = 'email_user';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'email_user', 'pass_user', 'mail_user', 'mail_port_user', 'protocol', 'security', 'lastupdated', 'created', 'status', 'smtp_host', 'smtp_port', 'smtp_security'];
    private const SUMMARY = 'id, user_id, email_user, mail_user, mail_port_user, protocol, security, lastupdated, created, status, smtp_host, smtp_port, smtp_security';

    private function owner(array $actor): string
    {
        $id = (string) ($actor['id'] ?? '');
        if ($id === '' || strlen($id) > 191) throw PageNotFoundException::forPageNotFound();
        return $id;
    }

    public function forUser(array $actor): array
    {
        return $this->select(self::SUMMARY)->where('user_id', $this->owner($actor))->orderBy('email_user')->findAll();
    }

    public function findOwned(int $id, array $actor): array
    {
        $row = $this->select(self::SUMMARY)->where('user_id', $this->owner($actor))->find($id);
        if ($row === null) throw PageNotFoundException::forPageNotFound();
        return $row;
    }

    public function saveFor(?int $id, array $data, array $actor): int
    {
        $owner = $this->owner($actor);
        if ($id !== null) $this->findOwned($id, $actor);
        foreach (['email_user', 'mail_user', 'mail_port_user', 'protocol', 'security', 'status', 'pass_user'] as $field) {
            if (!is_string($data[$field] ?? null)) throw new InvalidArgumentException('Confira os campos da conta.');
        }
        $email = trim($data['email_user']);
        $host = trim($data['mail_user']);
        if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Informe um e-mail válido.');
        if (strlen($host) > 253 || !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) throw new InvalidArgumentException('Informe um servidor de e-mail válido, como imap.exemplo.com.');
        $port = filter_var($data['mail_port_user'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        if ($port === false) throw new InvalidArgumentException('Informe uma porta entre 1 e 65535.');
        if (!in_array($data['protocol'], ['imap', 'pop3'], true) || !in_array($data['security'], ['ssl', 'tls', 'none'], true) || !in_array($data['status'], ['0', '1'], true)) throw new InvalidArgumentException('Protocolo, segurança ou status inválido.');
        $duplicate = $this->where('user_id', $owner)->where('email_user', $email)->first();
        if ($duplicate !== null && (int) $duplicate['id'] !== $id) throw new InvalidArgumentException('Este e-mail já está cadastrado.');
        $password = $data['pass_user'];
        if (($id === null && $password === '') || strlen($password) > 4096) throw new InvalidArgumentException('Informe uma senha com até 4096 caracteres.');
        $values = ['email_user' => $email, 'mail_user' => $host, 'mail_port_user' => $port, 'protocol' => $data['protocol'], 'security' => $data['security'], 'status' => (int) $data['status'], 'lastupdated' => gmdate('Y-m-d H:i:s')];
        if (array_key_exists('smtp_host', $data)) {
            $smtpHost = is_string($data['smtp_host']) ? trim($data['smtp_host']) : null;
            $smtpPort = filter_var($data['smtp_port'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
            if ($smtpHost === null || strlen($smtpHost) > 253 || ($smtpHost !== '' && !filter_var($smtpHost, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) || $smtpPort === false || !in_array($data['smtp_security'] ?? null, ['tls', 'ssl', 'none'], true)) throw new InvalidArgumentException('Confira o servidor, a porta e a segurança SMTP.');
            $values += ['smtp_host' => $smtpHost, 'smtp_port' => $smtpPort, 'smtp_security' => $data['smtp_security']];
        }
        if ($password !== '') {
            if ((string) config('Encryption')->key === '') throw new InvalidArgumentException('Configure encryption.key no servidor antes de salvar senhas.');
            $values['pass_user'] = base64_encode(service('encrypter')->encrypt($password));
        }
        if ($id === null) {
            $result = $this->insert($values + ['user_id' => $owner, 'created' => $values['lastupdated']]);
            if (!$result) throw new RuntimeException('Falha ao salvar a conta.');
            return (int) $result;
        }
        if (!$this->where('user_id', $owner)->update($id, $values)) throw new RuntimeException('Falha ao salvar a conta.');
        return $id;
    }

    public function connection(int $id, array $actor): array
    {
        $account = $this->findOwned($id, $actor);
        if ((int) $account['status'] !== 1) throw new InvalidArgumentException('Ative esta conta antes de consultar a caixa de entrada.');
        $row = $this->where('user_id', $this->owner($actor))->find($id);
        $cipher = base64_decode($row['pass_user'], true);
        if ($cipher === false) throw new RuntimeException('Credencial inválida.');
        $account['password'] = service('encrypter')->decrypt($cipher);
        return $account;
    }

    public function deleteFor(int $id, array $actor): void
    {
        $this->findOwned($id, $actor);
        if (!$this->where('user_id', $this->owner($actor))->delete($id)) throw new RuntimeException('Falha ao excluir a conta.');
    }
}
