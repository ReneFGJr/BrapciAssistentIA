<?php
require __DIR__ . '/notes.php';
verifyNote($model->saveFor($owner, array_replace($data, ['meeting_time' => '09:15']), $id), 'Save time');
verifyNote(substr($model->find($id)['meeting_time'], 0, 5) === '09:15', 'Time persisted');
foreach (['24:00', '12:60', 'noon', '9:15', '09:15:10'] as $time) {
    verifyNote(!$model->saveFor($owner, array_replace($data, ['meeting_time' => $time]), $id), 'Reject invalid time');
}
verifyNote($model->saveFor($owner, array_replace($data, ['meeting_time' => '']), $id), 'Clear time');
verifyNote($model->find($id)['meeting_time'] === null, 'Unknown time is null');
verifyNote($model->saveFor($owner, array_replace($data, ['meeting_time' => '08:00']), $id), 'Morning meeting');
verifyNote($model->saveFor($owner, array_replace($data, ['meeting_time' => '16:30']), $recentId), 'Afternoon meeting');
verifyNote((int) $model->visibleTo($owner)->findAll()[0]['id'] === $recentId, 'Order by date and time');
$attendee = $service->create(['nickname' => 'Carol', 'full_name' => '<b>Carol</b>'], $owner);
verifyNote($model->addParticipant($id, $attendee, $owner), 'Add participant');
verifyNote($model->addParticipant($id, $attendee, $owner), 'Repeated add is idempotent');
$participants = $model->participantsFor([$id], $owner);
verifyNote(count($participants[$id]) === 1, 'No duplicate participants');
deniedNote(fn () => $model->addParticipant($id, $other, $owner));
deniedNote(fn () => $model->addParticipant($id, $person, $stranger));
deniedNote(fn () => $model->removeParticipant($id, $attendee, $stranger));
verifyNote($model->participantsFor([$id], $stranger) === [], 'No unauthorized participant reads');
$db->table('persons_user')->where('user', 'reader')->update(['expires_at' => null, 'access_level' => 'read']);
verifyNote(count($model->participantsFor([$id], $reader)[$id]) === 1, 'Read-only meeting participants visible');
deniedNote(fn () => $model->addParticipant($id, $attendee, $reader));
deniedNote(fn () => $model->removeParticipant($id, $attendee, $reader));
verifyNote($model->addParticipant($recentId, $attendee, $owner), 'Same person at another meeting');
verifyNote($model->removeParticipant($id, $attendee, $owner), 'Remove participant');
verifyNote($model->participantsFor([$id], $owner) === [], 'Removed from correct meeting');
verifyNote(count($model->participantsFor([$recentId], $owner)[$recentId]) === 1, 'Other meeting preserved');
$html = view('notes/index', ['notes' => $model->visibleTo($owner)->findAll(), 'participantsByNote' => $model->participantsFor([$id, $recentId], $owner), 'editableIds' => [$person], 'pager' => new class { public function links() { return ''; } }]);
verifyNote(str_contains($html, '16:30') && str_contains($html, '&lt;b&gt;Carol&lt;/b&gt;') && !str_contains($html, '<b>Carol</b>'), 'Time and escaped participants shown');
$html = view('notes/form', ['note' => $model->find($recentId), 'persons' => (new App\Models\PersonModel($db))->findAll(), 'participants' => $model->participantsFor([$recentId], $owner)[$recentId], 'availableParticipants' => []]);
verifyNote(str_contains($html, 'type="time"') && str_contains($html, '/remove') && str_contains($html, 'Participantes da reunião'), 'Edit time and participants');
$model->delete($recentId);
verifyNote($db->table('note_participants')->where('note_id', $recentId)->countAllResults() === 0, 'Cascade cleanup');
echo "Meeting time and participants checks passed.\n";

verifyNote($model->createParticipant($id, 'Nova Participante', $owner), 'Create Person and add directly');
$createdParticipants = $model->participantsFor([$id], $owner)[$id];
verifyNote(count($createdParticipants) === 1 && $createdParticipants[0]['full_name'] === 'Nova Participante', 'New participant visible');
$service->access((int) $createdParticipants[0]['person_id'], $owner, true);
deniedNote(fn () => $model->createParticipant($id, 'Blocked', $reader));
$before = (new App\Models\PersonModel($db))->countAllResults();
try {
    $model->createParticipant($id, ' ', $owner);
    throw new RuntimeException('Empty participant accepted');
} catch (InvalidArgumentException $exception) {
    verifyNote((new App\Models\PersonModel($db))->countAllResults() === $before, 'Invalid name creates no Person');
}
$html = view('notes/participants', ['note' => $model->find($id), 'participants' => $createdParticipants, 'availableParticipants' => []]);
verifyNote(str_contains($html, 'participant_id') && str_contains($html, 'bg-dark text-light'), 'Empty list still offers new participant with readable text');
echo "Direct participant creation checks passed.\n";
