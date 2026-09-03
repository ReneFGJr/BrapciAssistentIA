<?php

namespace App\Models;

use CodeIgniter\Model;

class UserAppAccessModel extends Model
{
    protected $table = 'user_app_access';
    protected $primaryKey = 'user_id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'app_id', 'granted_at'];

    public function grant(string $userId, int $appId): bool
    {
        return (bool) $this->db->table($this->table)->replace([
            'user_id' => $userId,
            'app_id' => $appId,
            'granted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function revoke(string $userId, int $appId): bool
    {
        $this->db->table($this->table)->where(['user_id' => $userId, 'app_id' => $appId])->delete();

        return $this->db->affectedRows() > 0;
    }

    public function hasAccess(string $userId, int $appId): bool
    {
        return $this->db->table($this->table)
            ->where(['user_id' => $userId, 'app_id' => $appId])
            ->countAllResults() > 0;
    }

    public function getUserAppIds(string $userId): array
    {
        return array_map(
            'intval',
            array_column(
                $this->db->table($this->table)->select('app_id')->where('user_id', $userId)->get()->getResultArray(),
                'app_id'
            )
        );
    }
}
