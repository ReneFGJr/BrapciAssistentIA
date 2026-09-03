<?php

namespace App\Models;

use CodeIgniter\Model;

class ApplicationModel extends Model
{
    protected $table = 'apps';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['name', 'icon', 'url', 'access_level'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getAllApps(): array
    {
        return $this->orderBy('name', 'ASC')->findAll();
    }

    public function getAccessibleApps(string $userId): array
    {
        return $this->db->table($this->table . ' app')
            ->select('app.*')
            ->join(
                'user_app_access uaa',
                'uaa.app_id = app.id AND uaa.user_id = ' . $this->db->escape($userId),
                'left'
            )
            ->groupStart()
                ->where('app.access_level', 0)
                ->orWhere('uaa.user_id IS NOT NULL', null, false)
            ->groupEnd()
            ->orderBy('app.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function getApp(int $id): ?array
    {
        return $this->find($id);
    }

    public function createApp(array $data): int
    {
        return (int) $this->insert($data, true);
    }

    public function updateApp(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function deleteApp(int $id): bool
    {
        return $this->delete($id);
    }
}
