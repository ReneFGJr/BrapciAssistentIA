<?php
namespace App\Libraries;

use App\Models\GoogleScheduleModel;
use App\Models\KanbanModel;
use App\Models\NoteModel;
use DateTimeImmutable;
use DateTimeZone;

class DailySummary
{
    public function __construct(private ?\CodeIgniter\Database\BaseConnection $db = null) {}

    public function forUser(array $actor, ?DateTimeImmutable $now = null): array
    {
        $zone = new DateTimeZone('America/Sao_Paulo');
        $today = ($now ?? new DateTimeImmutable('now', $zone))->setTimezone($zone)->setTime(0, 0);
        $tomorrow = $today->modify('+1 day');
        $utc = new DateTimeZone('UTC');
        $start = $today->setTimezone($utc)->format('Y-m-d H:i:s');
        $end = $tomorrow->setTimezone($utc)->format('Y-m-d H:i:s');
        $userId = (string) $actor['id'];
        $events = (new GoogleScheduleModel($this->db))->where('user_id', $userId)
            ->where('starts_at <', $end)->where('ends_at >', $start)
            ->where('status !=', 'cancelled')->orderBy('starts_at')->findAll();
        $notes = (new NoteModel($this->db))->visibleTo($actor)->where('meeting_date', $today->format('Y-m-d'))
            ->where('notes.status !=', 'cancelled')->orderBy('meeting_time')->findAll();
        // Kanban timestamps use the application's configured timezone.
        $appZone = new DateTimeZone(config('App')->appTimezone);
        $tasks = (new KanbanModel($this->db))->where('user_id', $userId)
            ->where('updated_at >=', $today->setTimezone($appZone)->format('Y-m-d H:i:s'))
            ->where('updated_at <', $tomorrow->setTimezone($appZone)->format('Y-m-d H:i:s'))
            ->orderBy('updated_at', 'DESC')->findAll();
        return compact('today', 'events', 'notes', 'tasks');
    }
}
