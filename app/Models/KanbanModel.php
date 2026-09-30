<?php
namespace App\Models;
use CodeIgniter\Model;

class KanbanModel extends Model
{
    public const STATUSES = ['todo' => 'To DO', 'doing' => 'Doing', 'check' => 'Check', 'close' => 'Close'];
    public const PRIORITIES = ['low' => 'Sem pressa', 'normal' => 'Normal', 'urgent' => 'Urgente'];
    protected $table = 'kanban_items';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['user_id', 'title', 'description', 'status', 'priority', 'notes_id'];
    protected $validationRules = [
        'user_id' => 'required|max_length[191]',
        'title' => 'required|max_length[150]',
        'description' => 'permit_empty|max_length[10000]',
        'status' => 'required|in_list[todo,doing,check,close]',
        'priority' => 'required|in_list[low,normal,urgent]',
    ];

    public function forNote(int $noteId, array $actor): array
    {
        if ((new NoteModel($this->db))->visibleTo($actor)->where('notes.id', $noteId)->first() === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return $this->where('notes_id', $noteId)->forUser((string) $actor['id']);
    }

    public function createForNote(int $noteId, array $actor, array $data)
    {
        (new NoteModel($this->db))->editable($noteId, $actor);
        return $this->insert(['user_id' => (string) $actor['id'], 'notes_id' => $noteId]
            + array_intersect_key($data, array_flip(['title', 'description', 'status', 'priority'])));
    }

    public function forUser(string $userId): array
    {
        return $this->where('user_id', $userId)->orderBy('updated_at', 'DESC')->orderBy('id', 'DESC')->findAll();
    }

    public function owned(int $id, string $userId): ?array
    {
        return $this->where('user_id', $userId)->where('id', $id)->first();
    }

    public function createFor(string $userId, array $data)
    {
        return $this->insert(['user_id' => $userId] + array_intersect_key($data, array_flip(['title', 'description', 'status', 'priority'])));
    }

    public function updateFor(int $id, string $userId, array $data): bool
    {
        if ($this->owned($id, $userId) === null) {
            return false;
        }
        $data = array_intersect_key($data, array_flip(['title', 'description', 'status', 'priority']));
        return $this->where('user_id', $userId)->update($id, $data);
    }
}