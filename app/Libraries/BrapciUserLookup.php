<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseBuilder;

class BrapciUserLookup
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    private function matching(string $query): BaseBuilder
    {
        $builder = $this->db->table('brapci.users')
            ->select('id_us, us_nome, us_email, us_institution, us_affiliation');
        if (filter_var($query, FILTER_VALIDATE_EMAIL)) {
            return $builder->where('LOWER(us_email)', mb_strtolower($query));
        }
        return $builder->like('us_nome', $this->db->escapeLikeString($query));
    }

    public function search(string $query, int $page = 1): array
    {
        $builder = $this->matching($query);
        $total = $builder->countAllResults(false);
        $pages = max(1, (int) ceil($total / 20));
        $page = max(1, min($page, $pages));
        return [
            'total' => $total, 'page' => $page, 'pages' => $pages,
            'users' => $builder->orderBy('us_nome')->orderBy('id_us')
                ->get(20, ($page - 1) * 20)->getResultArray(),
        ];
    }

    public function findMatch(string $query, string $id): ?array
    {
        return $this->matching($query)->where('id_us', $id)->get()->getRowArray();
    }

    public static function prefill(array $user): array
    {
        $name = trim((string) ($user['us_nome'] ?? ''));
        $institution = trim((string) ($user['us_institution'] ?? ''));
        return [
            'nickname' => $name, 'full_name' => $name,
            'email_1' => trim((string) ($user['us_email'] ?? '')),
            'institution' => $institution !== '' ? $institution : trim((string) ($user['us_affiliation'] ?? '')),
        ];
    }

    public static function fromQuery(string $query): array
    {
        return filter_var($query, FILTER_VALIDATE_EMAIL)
            ? ['email_1' => $query]
            : ['nickname' => $query, 'full_name' => $query];
    }
}