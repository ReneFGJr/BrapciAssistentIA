<?php
namespace App\Models;
use App\Libraries\PersonAccessService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Model;
class NoteModel extends Model
{
    public const STATUSES = ['scheduled' => 'Agendada', 'completed' => 'Realizada', 'cancelled' => 'Cancelada'];
    protected $table = 'notes';
    protected $returnType = 'array';
    protected $allowedFields = ['meeting_date', 'meeting_time', 'title', 'description', 'status', 'person_id', 'user_id'];
    protected $validationRules = [
        'meeting_date' => 'required|valid_date[Y-m-d]',
        'meeting_time' => 'permit_empty|regex_match[/^([01][0-9]|2[0-3]):[0-5][0-9]$/]',
        'title' => 'required|max_length[255]',
        'description' => 'required|max_length[10000]',
        'status' => 'required|in_list[scheduled,completed,cancelled]',
        'person_id' => 'permit_empty|is_natural_no_zero',
    ];
    public function visibleTo(array $actor): self
    {
        return $this->select('notes.*, persons.full_name AS person_name')
            ->join('persons', 'persons.id = notes.person_id', 'left')
            ->groupStart()->where('notes.user_id', (string) ($actor['id'] ?? ''))
            ->orWhereIn('notes.person_id', (new PersonAccessService($this->db))->accessibleIds($actor))->groupEnd()
            ->orderBy('notes.meeting_date', 'DESC')->orderBy('notes.meeting_time', 'DESC')->orderBy('notes.id', 'DESC');
    }
    public function forPerson(int $personId, array $actor): self
    {
        (new PersonAccessService($this->db))->access($personId, $actor);
        $ids = $this->db->table('note_participants')->select('note_id')->where('person_id', $personId);
        return $this->visibleTo($actor)->groupStart()->where('notes.person_id', $personId)
            ->orWhereIn('notes.id', $ids)->groupEnd();
    }

    public function editable(int $id, array $actor): array
    {
        $note = $this->find($id);
        if ($note === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        if (empty($actor['id']) || (empty($note['person_id']) && (string) $note['user_id'] !== (string) $actor['id'])) {
            throw PageNotFoundException::forPageNotFound();
        }
        if (!empty($note['person_id'])) {
            (new PersonAccessService($this->db))->access((int) $note['person_id'], $actor, true);
        }
        return $note;
    }

    public function participantsFor(array $noteIds, array $actor): array
    {
        if ($noteIds === []) {
            return [];
        }
        $rows = $this->db->table('note_participants')->select('note_participants.*, persons.full_name')
            ->join('notes', 'notes.id = note_participants.note_id')
            ->join('persons', 'persons.id = note_participants.person_id')
            ->whereIn('notes.id', $noteIds)
            ->groupStart()->where('notes.user_id', (string) ($actor['id'] ?? ''))
            ->orWhereIn('notes.person_id', (new PersonAccessService($this->db))->accessibleIds($actor))->groupEnd()
            ->orderBy('persons.full_name')->orderBy('persons.id')->get()->getResultArray();
        $result = [];
        foreach ($rows as $row) {
            $result[$row['note_id']][] = $row;
        }
        return $result;
    }
    public function addParticipant(int $id, int $personId, array $actor): bool
    {
        $this->editable($id, $actor);
        (new PersonAccessService($this->db))->access($personId, $actor);
        if ((new PersonModel($this->db))->find($personId) === null) {
            throw PageNotFoundException::forPageNotFound();
        }
        $builder = $this->db->table('note_participants');
        if ($builder->where(['note_id' => $id, 'person_id' => $personId])->countAllResults() > 0) {
            return true;
        }
        return $builder->insert(['note_id' => $id, 'person_id' => $personId]);
    }
    public function createParticipant(int $id, string $name, array $actor): bool
    {
        $this->editable($id, $actor);
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 255) {
            throw new \InvalidArgumentException('Informe um nome com até 255 caracteres.');
        }
        $this->db->transBegin();
        try {
            $personId = (new PersonAccessService($this->db))->create([
                'full_name' => $name, 'nickname' => mb_substr($name, 0, 150),
            ], $actor);
            if (!$this->addParticipant($id, $personId, $actor) || !$this->db->transStatus()) {
                throw new \RuntimeException('Não foi possível adicionar o participante.');
            }
            $this->db->transCommit();
            return true;
        } catch (\Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }
    public function removeParticipant(int $id, int $personId, array $actor): bool
    {
        $this->editable($id, $actor);
        return $this->db->table('note_participants')
            ->where(['note_id' => $id, 'person_id' => $personId])->delete();
    }

    public function saveFor(array $actor, array $data, ?int $id = null): bool
    {
        if ($id !== null) {
            $existing = $this->editable($id, $actor);
        }
        unset($data['user_id']);
        if ($id === null) {
            if (empty($actor['id'])) {
                throw PageNotFoundException::forPageNotFound();
            }
            if (empty($data['person_id'])) {
                $data['person_id'] = null;
                $data['user_id'] = (string) $actor['id'];
            }
        } elseif (empty($existing['person_id'])) {
            $data['person_id'] = null;
        } elseif (!array_key_exists('person_id', $data)) {
            $data['person_id'] = $existing['person_id'];
        }
        $data = array_intersect_key($data, array_flip($this->allowedFields));
        if (! $this->validate($data)) {
            return false;
        }
        if (!empty($data['person_id'])) {
            (new PersonAccessService($this->db))->access((int) $data['person_id'], $actor, true);
        }
        if (array_key_exists('meeting_time', $data) && $data['meeting_time'] === '') {
            $data['meeting_time'] = null;
        }
        return $id === null ? $this->insert($data) !== false : $this->update($id, $data);
    }
}
