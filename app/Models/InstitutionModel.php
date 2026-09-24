<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

class InstitutionModel extends Model
{
    protected $table = 'institutions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'ror_id', 'name', 'acronym', 'address', 'city', 'state', 'country', 'country_code',
        'latitude', 'longitude', 'established_year',
    ];
    protected $validationRules = [
        'name' => 'required|max_length[255]',
        'ror_id' => 'permit_empty|regex_match[/^https:\/\/ror\.org\/0[0-9a-hj-km-np-tv-z]{6}[0-9]{2}$/]',
        'acronym' => 'permit_empty|max_length[100]',
        'address' => 'permit_empty|max_length[10000]',
        'city' => 'permit_empty|max_length[150]',
        'state' => 'permit_empty|max_length[150]',
        'country' => 'permit_empty|max_length[150]',
        'country_code' => 'permit_empty|alpha|exact_length[2]',
        'latitude' => 'permit_empty|decimal|greater_than_equal_to[-90]|less_than_equal_to[90]',
        'longitude' => 'permit_empty|decimal|greater_than_equal_to[-180]|less_than_equal_to[180]',
        'established_year' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[9999]',
    ];

    public function resolveName(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        $existing = $this->where('name', $name)->orderBy('id')->first();
        if ($existing !== null) {
            return (int) $existing['id'];
        }
        $id = $this->insert(['name' => $name]);
        if ($id === false) {
            throw new RuntimeException('Não foi possível cadastrar a instituição.');
        }
        return (int) $id;
    }
}