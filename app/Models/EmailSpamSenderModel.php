<?php
namespace App\Models;
use CodeIgniter\Model;
use InvalidArgumentException;
use RuntimeException;

class EmailSpamSenderModel extends Model
{
    protected $table = 'email_spam_senders';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'sender'];

    public static function address(string $from): ?string
    {
        $from = trim($from);
        if (preg_match('/<([^<>]+)>\s*$/D', $from, $match)) $from = trim($match[1]);
        $from = strtolower($from);
        return strlen($from) <= 254 && filter_var($from, FILTER_VALIDATE_EMAIL) ? $from : null;
    }
    public function forUser(array $actor): array
    {
        if (empty($actor['id'])) throw new InvalidArgumentException('Usuário inválido.');
        return $this->where('user_id', (string) $actor['id'])->orderBy('sender')->findAll();
    }
    public function blocked(string $from, array $actor): bool
    {
        $address = self::address($from);
        return $address !== null && $this->where('user_id', (string) $actor['id'])->where('sender', $address)->first() !== null;
    }
    public function blockMessage(int $accountId, int $messageId, array $actor): void
    {
        $rows = new EmailUserRowModel($this->db);
        $message = $rows->findOwned($accountId, $messageId, $actor);
        $sender = self::address($message['email_from']);
        if ($sender === null) throw new InvalidArgumentException('O remetente não possui um endereço de e-mail válido.');
        $this->db->transBegin();
        try {
            if (!$this->blocked($sender, $actor) && !$this->insert(['user_id' => (string) $actor['id'], 'sender' => $sender])) throw new RuntimeException('Falha ao salvar remetente.');
            foreach ($rows->where('user_id', (string) $actor['id'])->select('id,email_from')->findAll() as $row) {
                if (self::address($row['email_from']) === $sender && !$rows->update($row['id'], ['email_status' => 4, 'lastupdated' => gmdate('Y-m-d H:i:s')])) throw new RuntimeException('Falha ao classificar SPAM.');
            }
            if (!$this->db->transStatus()) throw new RuntimeException('Falha ao classificar SPAM.');
            $this->db->transCommit();
        } catch (\Throwable $error) { $this->db->transRollback(); throw $error; }
    }
}
