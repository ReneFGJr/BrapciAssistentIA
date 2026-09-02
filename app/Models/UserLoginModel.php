<?php

namespace App\Models;

use CodeIgniter\Model;

class UserLoginModel extends Model
{
    protected $table = 'user_logins';
    protected $primaryKey = 'user_id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'last_login_at'];
    protected $useTimestamps = false;

    public function recordLogin(string $userId): bool
    {
        return (bool) $this->db->table($this->table)->replace([
            'user_id'       => $userId,
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
