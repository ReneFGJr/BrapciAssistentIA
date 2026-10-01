<?php

namespace App\Models;

use CodeIgniter\Model;
use JsonException;
use RuntimeException;

class ChatContextModel extends Model
{
    protected $table = 'chat_context';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'title', 'context', 'created_at'];

    public function conversations(string $userId): array
    {
        return $this->select('id, title, created_at')->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->findAll();
    }

    public function createConversation(string $userId, string $title = 'new chat'): int
    {
        $title = trim($title) ?: 'new chat';
        if (mb_strlen($title) > 150) throw new RuntimeException('O título deve ter no máximo 150 caracteres.');
        $id = $this->insert([
            'user_id' => $userId,
            'title' => $title,
            'context' => '[]',
            'created_at' => date('Y-m-d H:i:s'),
        ], true);
        if ($id === false) throw new RuntimeException('Não foi possível criar a conversa.');
        return (int) $id;
    }

    public function conversation(int $id, string $userId): ?array
    {
        $row = $this->where(['id' => $id, 'user_id' => $userId])->first();
        if ($row === null) return null;
        try {
            $messages = json_decode((string) $row['context'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('O contexto armazenado da conversa é inválido.', 0, $exception);
        }
        $row['messages'] = is_array($messages) ? array_values(array_filter($messages, static fn ($message): bool =>
            is_array($message) && in_array($message['role'] ?? null, ['user', 'assistant'], true)
            && is_string($message['content'] ?? null))) : [];
        return $row;
    }

    public function renameConversation(int $id, string $userId, string $title): bool
    {
        $title = trim($title);
        if ($title === '' || mb_strlen($title) > 150) throw new RuntimeException('Informe um título com até 150 caracteres.');
        if ($this->conversation($id, $userId) === null) return false;
        return $this->where(['id' => $id, 'user_id' => $userId])->set(['title' => $title])->update();
    }

    public function saveMessages(int $id, string $userId, array $messages): bool
    {
        if ($this->conversation($id, $userId) === null) return false;
        return $this->where(['id' => $id, 'user_id' => $userId])->set([
            'context' => json_encode($messages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ])->update();
    }

    public function deleteConversation(int $id, string $userId): bool
    {
        $this->db->table($this->table)->where(['id' => $id, 'user_id' => $userId])->delete();
        return $this->db->affectedRows() > 0;
    }
}
