<?php

namespace App\Models;

use CodeIgniter\Encryption\Exceptions\EncryptionException;
use CodeIgniter\Model;
use RuntimeException;

class UserNoteModel extends Model
{
    protected $table = 'user_notes';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'title_encrypted',
        'content_encrypted',
        'created_at',
        'updated_at',
    ];

    public function createNote(string $userId, string $title, string $content): int
    {
        $now = date('Y-m-d H:i:s');

        $this->db->table($this->table)->insert([
            'user_id' => $userId,
            'title_encrypted' => $this->encrypt($title),
            'content_encrypted' => $this->encrypt($content),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->db->insertID();
    }

    public function getNotesByUser(string $userId): array
    {
        $rows = $this->db->table($this->table)
            ->where('user_id', $userId)
            ->orderBy('updated_at', 'DESC')
            ->get()
            ->getResultArray();

        return array_map(fn (array $row): array => $this->decryptRow($row), $rows);
    }

    public function getNote(int $id, string $userId): ?array
    {
        $row = $this->db->table($this->table)
            ->where(['id' => $id, 'user_id' => $userId])
            ->get()
            ->getRowArray();

        return $row === null ? null : $this->decryptRow($row);
    }

    public function updateNote(int $id, string $userId, string $title, string $content): bool
    {
        $this->db->table($this->table)
            ->where(['id' => $id, 'user_id' => $userId])
            ->update([
                'title_encrypted' => $this->encrypt($title),
                'content_encrypted' => $this->encrypt($content),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

        return $this->db->affectedRows() > 0;
    }

    public function deleteNote(int $id, string $userId): bool
    {
        $this->db->table($this->table)
            ->where(['id' => $id, 'user_id' => $userId])
            ->delete();

        return $this->db->affectedRows() > 0;
    }

    private function encrypt(string $value): string
    {
        if ((string) env('encryption.key', '') === '') {
            throw new RuntimeException('A variável encryption.key não está configurada.');
        }

        return base64_encode(service('encrypter')->encrypt($value));
    }

    private function decrypt(string $value): string
    {
        try {
            $decoded = base64_decode($value, true);
            if ($decoded === false) {
                throw new RuntimeException('Conteúdo criptografado inválido.');
            }

            return service('encrypter')->decrypt($decoded);
        } catch (EncryptionException $exception) {
            throw new RuntimeException(
                'Não foi possível descriptografar a anotação. Verifique a encryption.key.',
                0,
                $exception
            );
        }
    }

    private function decryptRow(array $row): array
    {
        $row['title'] = $this->decrypt($row['title_encrypted']);
        $row['content'] = $this->decrypt($row['content_encrypted']);
        unset($row['title_encrypted'], $row['content_encrypted']);

        return $row;
    }
}
