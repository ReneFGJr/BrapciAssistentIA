<?php

namespace App\Libraries;

use App\Models\PersonModel;
use App\Models\InstitutionModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Exceptions\PageNotFoundException;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class PersonAccessService
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    public static function email(mixed $email): string
    {
        return is_string($email) ? mb_strtolower(trim($email)) : '';
    }

    private function userId(array $actor): string
    {
        $id = (string) ($actor['id'] ?? '');
        if ($id === '' || strlen($id) > 191) {
            throw PageNotFoundException::forPageNotFound();
        }
        return $id;
    }

    // Only the e-mail returned by the authentication provider may claim a grant.
    private function claim(array $actor): void
    {
        $id = $this->userId($actor);
        $email = self::email($actor['email'] ?? null);
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        $this->db->table('persons_user')->where('user', null)
            ->where('email', $email)->where('user_own !=', $id)->update(['user' => $id]);
    }

    public function accessibleIds(array $actor): BaseBuilder
    {
        $this->claim($actor);
        return $this->db->table('persons_user')->select('person_id')
            ->where('user', $this->userId($actor))
            ->whereIn('access_level', ['read', 'edit'])
            ->groupStart()->where('expires_at', null)
            ->orWhere('expires_at >', gmdate('Y-m-d H:i:s'))->groupEnd();
    }

    public function access(int $personId, array $actor, bool $edit = false, bool $owner = false): array
    {
        $this->claim($actor);
        $grant = $this->db->table('persons_user')
            ->where('person_id', $personId)->where('user', $this->userId($actor))
            ->whereIn('access_level', ['read', 'edit'])
            ->groupStart()->where('expires_at', null)
            ->orWhere('expires_at >', gmdate('Y-m-d H:i:s'))->groupEnd()
            ->orderBy('access_level', 'ASC')->get()->getRowArray();
        if ($grant === null || ($edit && $grant['access_level'] !== 'edit')
            || ($owner && $grant['user_own'] !== $this->userId($actor))) {
            throw PageNotFoundException::forPageNotFound('Cadastro não encontrado ou acesso indisponível.');
        }
        return $grant;
    }

    public function create(array $data, array $actor): int
    {
        $userId = $this->userId($actor);
        $this->db->transBegin();
        try {
            $model = new PersonModel($this->db);
            $id = $model->insert($this->withInstitution($data));
            if ($id === false || ! $this->db->table('persons_user')->insert([
                'person_id' => $id, 'user_own' => $userId, 'user' => $userId,
                'email' => null, 'access_level' => 'edit', 'expires_at' => null,
            ]) || ! $this->db->transStatus()) {
                throw new RuntimeException('Não foi possível criar o cadastro.');
            }
            $this->db->transCommit();
            return (int) $id;
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

    public function update(int $personId, array $data, array $actor): bool
    {
        $this->access($personId, $actor, true);
        $this->db->transBegin();
        try {
            $saved = (new PersonModel($this->db))->update($personId, $this->withInstitution($data));
            if (! $saved || ! $this->db->transStatus()) {
                $this->db->transRollback();
                return false;
            }
            $this->db->transCommit();
            return true;
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }

    private function withInstitution(array $data): array
    {
        unset($data['institution']);
        if (array_key_exists('institution_id', $data)) {
            $id = $data['institution_id'];
            if ($id === null || $id === '') {
                $data['institution_id'] = null;
            } else {
                if ((! is_int($id) && ! is_string($id)) || ! ctype_digit((string) $id)
                    || (int) $id < 1 || (new InstitutionModel($this->db))->find($id) === null) {
                    throw new InvalidArgumentException('Selecione uma instituição cadastrada.');
                }
                $data['institution_id'] = (int) $id;
            }
        }
        return $data;
    }
    public function share(int $personId, array $actor, string $email, string $level, string $expiration): void
    {
        $this->access($personId, $actor, false, true);
        $email = self::email($email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            throw new InvalidArgumentException('Informe um e-mail válido.');
        }
        if ($email === self::email($actor['email'] ?? null)) {
            throw new InvalidArgumentException('O dono já possui acesso de edição permanente.');
        }
        if (! in_array($level, ['read', 'edit'], true)) {
            throw new InvalidArgumentException('Selecione leitura ou edição.');
        }

        $expiresAt = null;
        if ($expiration !== '') {
            $timezone = new DateTimeZone(config('App')->appTimezone);
            $date = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $expiration, $timezone);
            if ($date === false || $date->format('Y-m-d\\TH:i') !== $expiration || $date->getTimestamp() <= time()) {
                throw new InvalidArgumentException('A expiração deve ser uma data futura válida.');
            }
            $expiresAt = $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        }

        $existing = $this->db->table('persons_user')->where('person_id', $personId)
            ->where('email', $email)->get()->getRowArray();
        if ($existing !== null && $existing['user'] === $existing['user_own']) {
            throw new InvalidArgumentException('O acesso do dono não pode ser alterado.');
        }
        $data = ['access_level' => $level, 'expires_at' => $expiresAt];
        $builder = $this->db->table('persons_user');
        $saved = $existing !== null
            ? $builder->where('id', $existing['id'])->update($data)
            : $builder->insert($data + [
                'person_id' => $personId, 'user_own' => $this->userId($actor), 'email' => $email, 'user' => null,
            ]);
        if (! $saved) {
            throw new RuntimeException('Não foi possível compartilhar o cadastro.');
        }
    }

    public function shares(int $personId, array $actor): array
    {
        $this->access($personId, $actor, false, true);
        return $this->db->table('persons_user')->where('person_id', $personId)
            ->where('email !=', null)->orderBy('email')->get()->getResultArray();
    }

    public function revoke(int $personId, int $grantId, array $actor): void
    {
        $this->access($personId, $actor, false, true);
        $this->db->table('persons_user')->where('person_id', $personId)->where('id', $grantId)
            ->groupStart()->where('user', null)->orWhere('user !=', $this->userId($actor))->groupEnd()->delete();
    }
}