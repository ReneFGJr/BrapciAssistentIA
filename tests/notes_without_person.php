<?php
require __DIR__ . '/notes.php';
$creator = ['id' => 'new-user', 'email' => 'new@example.test'];
$fields = ['meeting_date' => '2026-09-30', 'meeting_time' => '14:30', 'title' => 'Sem Person', 'description' => 'Reunião', 'status' => 'scheduled', 'user_id' => 'stranger'];
verifyNote($model->saveFor($creator, $fields), 'Create without Person');
$newId = (int) $model->getInsertID();
$note = $model->editable($newId, $creator);
verifyNote($note['person_id'] === null && $note['user_id'] === 'new-user', 'Creator from authentication');
verifyNote(count($model->visibleTo($creator)->findAll()) === 1, 'Creator sees note');
deniedNote(fn () => $model->editable($newId, $stranger));
verifyNote($model->saveFor($creator, array_replace($fields, ['title' => 'Atualizada']), $newId), 'Edit without Person');
verifyNote($model->find($newId)['user_id'] === 'new-user', 'Owner retained');
verifyNote($model->participantsFor([$newId], $creator) === [], 'Empty participants supported');
$html = view('notes/form', ['note' => null, 'persons' => [], 'participants' => []]);
verifyNote(!str_contains($html, 'name="person_id"') && str_contains($html, 'id="notes-form"'), 'Form available without Persons');
echo "Notes without Person checks passed.\n";
