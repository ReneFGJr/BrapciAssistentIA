<?php
namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

class EmailSentModel extends Model
{
    protected $table = 'email_sent';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'email_user_id', 'original_id', 'original_message_id', 'action', 'sender', 'recipients', 'cc', 'subject', 'body', 'reply_text', 'status', 'sent_at'];

    public function recordPending(array $message, array $actor, array $account, string $action, string $reply, array $mail): int
    {
        (new EmailUserRowModel($this->db))->findOwned((int) $account['id'], (int) $message['id'], $actor);
        $id = $this->insert([
            'user_id' => (string) $actor['id'], 'email_user_id' => (int) $account['id'],
            'original_id' => (int) $message['id'], 'original_message_id' => $message['message_id'] ?? null,
            'action' => $action, 'sender' => $account['email_user'], 'recipients' => $mail['to'],
            'cc' => $mail['cc'] ?? '', 'subject' => $mail['subject'], 'body' => $mail['body'],
            'reply_text' => $reply, 'status' => 'pending',
        ]);
        if (!$id) throw new RuntimeException('Não foi possível preparar o registro do envio.');
        return (int) $id;
    }

    public function confirmSent(int $id, array $actor): void
    {
        if (!$this->where('user_id', (string) $actor['id'])->update($id, ['status' => 'sent', 'sent_at' => gmdate('Y-m-d H:i:s')])) {
            throw new RuntimeException('Não foi possível registrar a confirmação do envio.');
        }
    }

    public function forUser(array $actor): array
    {
        if (empty($actor['id'])) return [];
        return $this->where('user_id', (string) $actor['id'])->where('status', 'sent')->orderBy('sent_at', 'DESC')->orderBy('id', 'DESC')->findAll();
    }
}
