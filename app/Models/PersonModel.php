<?php
namespace App\Models;

use CodeIgniter\Model;

class PersonModel extends Model
{
    protected $table = 'persons';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'nickname', 'full_name', 'cpf', 'phone_1', 'phone_2',
        'email_1', 'email_2', 'institution_id', 'photo',
    ];

    public function searchByName(string $search): self
    {
        if ($search !== '') {
            $this->groupStart()->like('nickname', $search)->orLike('full_name', $search)->groupEnd();
        }
        return $this->orderBy('nickname', 'ASC')->orderBy('id', 'ASC');
    }
}