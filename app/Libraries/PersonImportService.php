<?php
namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;

class PersonImportService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null, private ?\Closure $photoDownloader = null)
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
            $result = ['imported' => 0, 'duplicates' => 0, 'invalid' => 0, 'photos' => 0, 'photo_errors' => 0, 'photo_pending' => 0, 'ambiguous' => 0];
            $names = $phones = $people = $photos = [];
            $owned = $this->db->table('persons p')->select('p.*')->distinct()
                ->join('persons_user pu', 'pu.person_id = p.id')
                ->where('pu.user_own', (string) ($actor['id'] ?? ''))
                ->where('pu.user', (string) ($actor['id'] ?? ''))->get()->getResultArray();
            foreach ($owned as $person) {
                $people[$person['id']] = $person;
                self::indexContact($names, $phones, (int) $person['id'], $person);
            }
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
                $contact = array_combine($header, $row);
                $matches = $names[self::nameKey($data['full_name'])] ?? [];
                foreach (['phone_1', 'phone_2'] as $field) {
                    $key = self::phoneKey($data[$field] ?? '');
                    if ($key !== '') {
                        $matches += $phones[$key] ?? [];
                    }
                }
                if ($matches !== []) {
                    $result['duplicates']++;
                    if (count($matches) > 1) {
                        // Never guess which existing person should receive a photo.
                        $result['ambiguous']++;
                        continue;
                    }
                    $id = (int) array_key_first($matches);
                } else {
                    $id = $service->create($data, $actor);
                    $people[$id] = $data + ['photo' => null];
                    $result['imported']++;
                }
                self::indexContact($names, $phones, $id, $data);
                $url = trim((string) ($contact['Photo'] ?? ''));
                if ($url !== '' && empty($people[$id]['photo'])) {
                    $photos[$id] ??= $url;
                }
            }
            if (! feof($file) || ! $this->db->transStatus() || ! $this->db->transCommit()) {
                throw new RuntimeException('Falha na importação dos contatos.');
            }
            $started = false;
            // Downloads run after the contact transaction, without holding database locks.
            $deadline = microtime(true) + 20;
            foreach ($photos as $id => $url) {
                if (microtime(true) >= $deadline) {
                    $result['photo_pending']++;
                    continue;
                }
                $failureKey = 'contact_photo_failure_' . hash('sha256', $url);
                if ($this->photoDownloader === null && cache()->get($failureKey)) {
                    $result['photo_errors']++;
                    continue;
                }
                $filename = null;
                try {
                    $filename = $this->photoDownloader !== null
                        ? ($this->photoDownloader)($url)
                        : (new ContactPhotoDownloader())->download($url);
                    if (! preg_match('/^[a-f0-9]{32}\.jpg$/', $filename)) {
                        throw new RuntimeException('Nome de fotografia inválido.');
                    }
                    if (! $this->db->table('persons')->where('id', $id)
                        ->groupStart()->where('photo', null)->orWhere('photo', '')->groupEnd()
                        ->update(['photo' => $filename])) {
                        throw new RuntimeException('Falha ao vincular fotografia.');
                    }
                    if ($this->db->affectedRows() === 0) {
                        $this->removePhoto($filename);
                    } else {
                        $result['photos']++;
                    }
                } catch (Throwable $exception) {
                    $this->removePhoto($filename);
                    if ($this->photoDownloader === null) {
                        cache()->save($failureKey, true, 300);
                    }
                    $result['photo_errors']++;
                    log_message('error', 'Falha na fotografia de contato {id}: {message}', [
                        'id' => $id, 'message' => $exception->getMessage(),
                    ]);
                }
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

    private function removePhoto(?string $name): void
    {
        if ($name !== null && preg_match('/^[a-f0-9]{32}\.jpg$/', $name)) {
            $path = FCPATH . 'repository/photo/' . $name;
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public static function nameKey(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)));
    }

    public static function phoneKey(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '0') && in_array(strlen($digits), [13, 14], true)) {
            $digits = substr($digits, 3); // Brazilian carrier prefix.
        } elseif (str_starts_with($digits, '0') && in_array(strlen($digits), [11, 12], true)) {
            $digits = substr($digits, 1);
        }
        if (in_array(strlen($digits), [10, 11], true) && ! str_starts_with(trim($phone), '+')) {
            $digits = '55' . $digits;
        }
        return $digits;
    }

    private static function indexContact(array &$names, array &$phones, int $id, array $data): void
    {
        $name = self::nameKey($data['full_name']);
        if ($name !== '') {
            $names[$name][$id] = true;
        }
        foreach (['phone_1', 'phone_2'] as $field) {
            $phone = self::phoneKey((string) ($data[$field] ?? ''));
            if ($phone !== '') {
                $phones[$phone][$id] = true;
            }
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