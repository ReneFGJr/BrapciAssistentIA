<?php
namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

class PersonImportService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public function import(string $path, array $actor): array
    {
        $file = @fopen($path, 'rb');
        if ($file === false) {
            throw new RuntimeException('Arquivo de contatos indisponível.');
        }
        $started = false;
        try {
            $header = fgetcsv($file, 0, ',', '"', '');
            if ($header === false) {
                throw new RuntimeException('CSV vazio.');
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            if (! in_array('First Name', $header, true) || ! in_array('E-mail 1 - Value', $header, true)) {
                throw new RuntimeException('Cabeçalho de contatos inválido.');
            }
            $result = ['imported' => 0, 'duplicates' => 0, 'invalid' => 0];
            $service = new PersonAccessService($this->db);
            $this->db->transBegin();
            $started = true;
            while (($row = fgetcsv($file, 0, ',', '"', '')) !== false) {
                if ($row === [null]) {
                    continue;
                }
                if (count($row) !== count($header) || ($data = self::map(array_combine($header, $row))) === null) {
                    $result['invalid']++;
                    continue;
                }
                $existing = $this->db->table('persons p')->join('persons_user pu', 'pu.person_id = p.id')
                    ->where('pu.user_own', (string) ($actor['id'] ?? ''))
                    ->where('pu.user', (string) ($actor['id'] ?? ''));
                foreach ($data as $field => $value) {
                    $existing->where('p.' . $field, $value);
                }
                if ($existing->countAllResults() > 0) {
                    $result['duplicates']++;
                    continue;
                }
                $service->create($data, $actor);
                $result['imported']++;
            }
            if (! feof($file) || ! $this->db->transStatus() || ! $this->db->transCommit()) {
                throw new RuntimeException('Falha na importação dos contatos.');
            }
            return $result;
        } catch (Throwable $exception) {
            if ($started) {
                $this->db->transRollback();
            }
            throw $exception;
        } finally {
            fclose($file);
        }
    }

    public static function map(array $row): ?array
    {
        $get = static fn (string $key): string => trim((string) ($row[$key] ?? ''));
        $name = implode(' ', array_filter(array_map($get, ['Name Prefix', 'First Name', 'Middle Name', 'Last Name', 'Name Suffix']),
            static fn (string $value): bool => $value !== ''));
        $emails = $phones = [];
        foreach ($row as $key => $value) {
            if (preg_match('/^E-mail \d+ - Value$/', $key)) {
                foreach (explode(' ::: ', $value) as $email) {
                    $email = PersonAccessService::email($email);
                    if ($email !== '') {
                        $emails[] = $email;
                    }
                }
            } elseif (preg_match('/^Phone \d+ - Value$/', $key)) {
                foreach (explode(' ::: ', $value) as $phone) {
                    if (trim($phone) !== '') {
                        $phones[] = trim($phone);
                    }
                }
            }
        }
        $emails = array_values(array_unique($emails));
        $phones = array_values(array_unique($phones));
        $name = $name ?: ($get('Nickname') ?: ($get('Organization Name') ?: ($emails[0] ?? $phones[0] ?? '')));
        $data = ['full_name' => $name, 'nickname' => $get('Nickname') ?: mb_substr($name, 0, 150),
            'email_1' => $emails[0] ?? null, 'email_2' => $emails[1] ?? null,
            'phone_1' => $phones[0] ?? null, 'phone_2' => $phones[1] ?? null];
        foreach (['full_name' => 255, 'nickname' => 150, 'email_1' => 254, 'email_2' => 254, 'phone_1' => 30, 'phone_2' => 30] as $field => $limit) {
            if ($data[$field] !== null && (! mb_check_encoding($data[$field], 'UTF-8') || mb_strlen($data[$field]) > $limit)) {
                return null;
            }
        }
        foreach (['email_1', 'email_2'] as $field) {
            if ($data[$field] !== null && ! filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                return null;
            }
        }
        return $name === '' ? null : $data;
    }
}