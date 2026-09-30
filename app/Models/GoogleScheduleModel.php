<?php
namespace App\Models;
use CodeIgniter\Model;
use CodeIgniter\Exceptions\PageNotFoundException;
use RuntimeException;
use Throwable;
class GoogleScheduleModel extends Model
{
    protected $table = 'service_google_schedule';
    protected $returnType = 'array';
    public function upcoming(array $service): self
    {
        return $this->where('service_id', $service['id'])->where('user_id', $service['user_id'])
            ->where('calendar_id', $service['email'])->where('ends_at >', gmdate('Y-m-d H:i:s'))
            ->where('status !=', 'cancelled')->orderBy('starts_at')->orderBy('id');
    }
    public function upcomingForUser(string $userId): self
    {
        return $this->where('user_id', $userId)->where('ends_at >', gmdate('Y-m-d H:i:s'))
            ->where('status !=', 'cancelled')->orderBy('starts_at')->orderBy('id');
    }
    public function latestSyncForUser(string $userId): ?string
    {
        $row = $this->selectMax('synced_at')->where('user_id', $userId)->first();
        return is_string($row['synced_at'] ?? null) && $row['synced_at'] !== '' ? $row['synced_at'] : null;
    }
    public function replaceSnapshot(array $service, array $events): void
    {
        $this->db->transBegin();
        try {
            $now = gmdate('Y-m-d H:i:s');
            $this->db->table($this->table)->where('service_id', $service['id'])
                ->where('user_id', $service['user_id'])->update(['status' => 'cancelled', 'updated_at' => $now]);
            foreach ($events as $event) {
                $existing = $this->db->table($this->table)->where('service_id', $service['id'])
                    ->where('google_event_id', $event['google_event_id'])->get()->getRowArray();
                $data = array_intersect_key($event, array_flip(['google_event_id', 'title', 'description',
                    'location', 'starts_at', 'ends_at', 'all_day', 'timezone', 'status']));
                $data += ['service_id' => $service['id'], 'user_id' => $service['user_id'],
                    'calendar_id' => $service['email'], 'synced_at' => $now, 'updated_at' => $now];
                $builder = $this->db->table($this->table);
                $saved = $existing === null
                    ? $builder->insert($data + ['created_at' => $now])
                    : $builder->where('id', $existing['id'])->update($data);
                if (!$saved) throw new RuntimeException('Falha ao salvar agenda.');
            }
            if (!$this->db->transStatus()) throw new RuntimeException('Falha ao salvar agenda.');
            $this->db->transCommit();
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }
    public function assignSubject(int $id, ?int $subjectId, array $service): bool
    {
        $event = $this->where('id', $id)->where('service_id', $service['id'])
            ->where('user_id', $service['user_id'])->first();
        if ($event === null) throw PageNotFoundException::forPageNotFound();
        if ($subjectId !== null && (new SubjectModel($this->db))->where('id', $subjectId)
            ->where('user_id', $service['user_id'])->first() === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        return $this->db->table($this->table)->where('id', $id)->where('user_id', $service['user_id'])
            ->update(['subject_id' => $subjectId, 'updated_at' => gmdate('Y-m-d H:i:s')]);
    }
    public function assignSubjectForUser(int $id, ?int $subjectId, string $userId): bool
    {
        if ($this->where('id', $id)->where('user_id', $userId)->first() === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        if ($subjectId !== null && (new SubjectModel($this->db))->where('id', $subjectId)
            ->where('user_id', $userId)->first() === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        return $this->db->table($this->table)->where('id', $id)->where('user_id', $userId)
            ->update(['subject_id' => $subjectId, 'updated_at' => gmdate('Y-m-d H:i:s')]);
    }
}
