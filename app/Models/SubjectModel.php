<?php
namespace App\Models;
use CodeIgniter\Model;
use CodeIgniter\Exceptions\PageNotFoundException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;
class SubjectModel extends Model
{
    protected $table = 'subjects';
    protected $returnType = 'array';
    protected $allowedFields = ['user_id', 'name'];

    private function userId(array $actor): string
    {
        $id = (string) ($actor['id'] ?? '');
        if ($id === '' || strlen($id) > 191) {
            throw PageNotFoundException::forPageNotFound();
        }
        return $id;
    }
    public function forUser(array $actor): array
    {
        return $this->where('user_id', $this->userId($actor))->orderBy('name')->findAll();
    }
    public function forNote(int $noteId, array $actor): array
    {
        if ((new NoteModel($this->db))->visibleTo($actor)->where('notes.id', $noteId)->first() === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return $this->select('subjects.*')->join('note_subjects', 'note_subjects.subject_id = subjects.id')
            ->where('note_subjects.note_id', $noteId)->where('subjects.user_id', $this->userId($actor))
            ->orderBy('subjects.name')->findAll();
    }
    public function relatedNotes(int $subjectId, int $sourceId, array $actor): NoteModel
    {
        $categories = $this->forNote($sourceId, $actor);
        if (!in_array($subjectId, array_column($categories, 'id'))) {
            throw PageNotFoundException::forPageNotFound();
        }
        $ids = $this->db->table('note_subjects')->select('note_id')->where('subject_id', $subjectId);
        return (new NoteModel($this->db))->visibleTo($actor)->whereIn('notes.id', $ids);
    }

    private function owned(int $id, array $actor): void
    {
        if ($this->where('id', $id)->where('user_id', $this->userId($actor))->first() === null) {
            throw PageNotFoundException::forPageNotFound();
        }
    }
    public function attach(int $noteId, int $subjectId, array $actor): bool
    {
        (new NoteModel($this->db))->editable($noteId, $actor);
        $this->owned($subjectId, $actor);
        $data = ['note_id' => $noteId, 'subject_id' => $subjectId];
        if ($this->db->table('note_subjects')->where($data)->countAllResults() > 0) {
            return true;
        }
        return $this->db->table('note_subjects')->insert($data);
    }
    public function detach(int $noteId, int $subjectId, array $actor): bool
    {
        (new NoteModel($this->db))->editable($noteId, $actor);
        $this->owned($subjectId, $actor);
        return $this->db->table('note_subjects')->where(['note_id' => $noteId, 'subject_id' => $subjectId])->delete();
    }
    public function createAndAttach(int $noteId, string $name, array $actor): bool
    {
        (new NoteModel($this->db))->editable($noteId, $actor);
        $userId = $this->userId($actor);
        $name = preg_replace('/\s+/u', ' ', trim($name));
        if ($name === null || $name === '' || mb_strlen($name) > 150) {
            throw new InvalidArgumentException('Informe uma categoria com até 150 caracteres.');
        }
        $this->db->transBegin();
        try {
            $existing = $this->where('user_id', $userId)->where('name', $name)->first();
            $id = $existing['id'] ?? $this->insert(['user_id' => $userId, 'name' => $name]);
            if (!$id || !$this->attach($noteId, (int) $id, $actor) || !$this->db->transStatus()) {
                throw new RuntimeException('Não foi possível salvar a categoria.');
            }
            $this->db->transCommit();
            return true;
        } catch (Throwable $exception) {
            $this->db->transRollback();
            throw $exception;
        }
    }
}
