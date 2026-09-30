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

    public function visibleInDirectory(array $actor): self
    {
        return $this->whereIn('id',
            (new \App\Libraries\PersonAccessService($this->db))->accessibleIds($actor));
    }

    public function searchByWords(string $search): self
    {
        foreach (preg_split('/\\s+/u', trim($search), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $word = $this->db->escapeLikeString($word);
            $this->groupStart()->like('full_name', $word, 'both', true)->orLike('nickname', $word, 'both', true)->groupEnd();
        }
        return $this;
    }

    public function searchByName(string $search): self
    {
        if ($search !== '') {
            $this->groupStart()->like('nickname', $search)->orLike('full_name', $search)->groupEnd();
        }
        return $this->orderBy('nickname', 'ASC')->orderBy('id', 'ASC');
    }
}