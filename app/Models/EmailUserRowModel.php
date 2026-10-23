<?php
namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Exceptions\PageNotFoundException;
use InvalidArgumentException;
use RuntimeException;

class EmailUserRowModel extends Model
{
    protected $table = 'email_user_row';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'email_user_id', 'remote_key', 'message_id', 'email_from', 'email_to', 'email_title', 'email_context', 'email_data', 'email_status', 'has_attachments', 'size_bytes', 'folder', 'created', 'lastupdated'];

    public function forAccount(int $id, array $actor): self
    {
        (new EmailUserModel($this->db))->findOwned($id, $actor);
        return $this->where('email_user_id', $id)->where('user_id', (string) $actor['id'])->orderBy('email_data', 'DESC')->orderBy('id', 'DESC');
    }

    public function forUser(array $actor): self
    {
        $accounts = (new EmailUserModel($this->db))->forUser($actor);
        return $this->where('user_id', (string) $actor['id'])
            ->whereIn('email_user_id', array_column($accounts, 'id') ?: [0])
            ->orderBy('email_data', 'DESC')->orderBy('id', 'DESC');
    }

    public function known(int $id, string $key, array $actor): bool
    {
        return $this->forAccount($id, $actor)->where('remote_key', $key)->first() !== null;
    }

    public function unified(array $actor): array
    {
        $accounts = array_column((new EmailUserModel($this->db))->forUser($actor), null, 'id');
        $groups = [];
        foreach ($this->forUser($actor)->findAll() as $row) {
            $messageId = trim((string) ($row['message_id'] ?? ''));
            $fingerprint = $messageId !== '' ? 'id:' . $messageId : 'body:' . hash('sha256', json_encode([
                mb_strtolower(trim($row['email_from'])), trim($row['email_title']), $row['email_data'],
                trim(str_replace(["\r\n", "\r"], "\n", $row['email_context'])),
            ], JSON_THROW_ON_ERROR));
            $groups[$fingerprint][] = $row;
        }
        $visible = [];
        foreach ($groups as $copies) {
            // Deduplicate between accounts, preserving distinct messages within one mailbox.
            usort($copies, static function ($a, $b) use ($accounts) {
                $preferred = (int) ($accounts[$b['email_user_id']]['is_preferred'] ?? 0) <=> (int) ($accounts[$a['email_user_id']]['is_preferred'] ?? 0);
                return $preferred ?: ((int) $a['email_user_id'] <=> (int) $b['email_user_id']) ?: ((int) $b['id'] <=> (int) $a['id']);
            });
            $chosenAccount = $copies[0]['email_user_id'];
            foreach ($copies as $copy) if ((string) $copy['email_user_id'] === (string) $chosenAccount && empty($copy['server_deleted_at'])) $visible[] = $copy;
        }
        usort($visible, static fn($a, $b) => strcmp($b['email_data'] ?? '', $a['email_data'] ?? '') ?: ((int) $b['id'] <=> (int) $a['id']));
        return $visible;
    }

    public function import(int $id, array $message, array $actor): bool
    {
        if ($this->known($id, $message['remote_key'], $actor)) return false;
        // Only imported fields are accepted; ownership comes from the session.
        $data = array_intersect_key($message, array_flip(['remote_key', 'message_id', 'email_from', 'email_to', 'email_title', 'email_context', 'email_data', 'email_status', 'has_attachments', 'size_bytes']));
        if ((new EmailSpamSenderModel($this->db))->blocked((string) ($data['email_from'] ?? ''), $actor)) $data['email_status'] = 4;
        $now = gmdate('Y-m-d H:i:s');
        if (!$this->insert($data + ['user_id' => (string) $actor['id'], 'email_user_id' => $id, 'folder' => 'INBOX', 'created' => $now, 'lastupdated' => $now])) throw new RuntimeException('Falha ao armazenar mensagem.');
        return true;
    }

    public function findOwned(int $accountId, int $id, array $actor): array
    {
        $row = $this->forAccount($accountId, $actor)->where('id', $id)->first();
        if ($row === null) throw PageNotFoundException::forPageNotFound();
        return $row;
    }

    public function deleteFor(int $accountId, int $id, array $actor): void
    {
        $this->findOwned($accountId, $id, $actor);
        if (!$this->where('user_id', (string) $actor['id'])->where('email_user_id', $accountId)->delete($id)) throw new RuntimeException('A mensagem foi excluída do servidor, mas não foi possível remover a cópia local.');
    }

    public function markServerDeleted(int $accountId, int $id, array $actor): void
    {
        $row = $this->findOwned($accountId, $id, $actor);
        if (!empty($row['server_deleted_at'])) return;
        $now = gmdate('Y-m-d H:i:s');
        if (!$this->db->table($this->table)->where('id', $id)->where('email_user_id', $accountId)->where('user_id', (string) $actor['id'])
            ->update(['server_deleted_at' => $now, 'lastupdated' => $now])) throw new RuntimeException('Não foi possível registrar a exclusão no servidor. A mensagem permanece no banco.');
    }

    public function markRead(int $accountId, int $id, array $actor): int
    {
        $this->findOwned($accountId, $id, $actor);
        // A concurrent manual classification must not be overwritten by the timer.
        if (!$this->where('user_id', (string) $actor['id'])->where('email_user_id', $accountId)->where('email_status', 0)
            ->update($id, ['email_status' => 1, 'lastupdated' => gmdate('Y-m-d H:i:s')])) throw new RuntimeException('Falha ao marcar mensagem como lida.');
        return (int) $this->findOwned($accountId, $id, $actor)['email_status'];
    }

    public function changeStatus(int $accountId, int $id, string $status, array $actor): void
    {
        $row = $this->forAccount($accountId, $actor)->where('id', $id)->first();
        if ($row === null) throw PageNotFoundException::forPageNotFound();
        if (!in_array($status, ['0', '1', '2', '3'], true)) throw new InvalidArgumentException('Status inválido.');
        if ((int) $row['email_status'] === 4) throw new InvalidArgumentException('Esta mensagem está classificada como SPAM.');
        if (!$this->where('user_id', (string) $actor['id'])->where('email_user_id', $accountId)->update($id, ['email_status' => (int) $status, 'lastupdated' => gmdate('Y-m-d H:i:s')])) throw new RuntimeException('Falha ao alterar status.');
    }
}
