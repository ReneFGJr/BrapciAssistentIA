<?php
require __DIR__ . '/notes.php';
$db->table('note_participants')->insert(['note_id' => $id, 'person_id' => $person]);
$rows = (new App\Models\NoteModel($db))->forPerson($person, $owner)->findAll();
verifyNote(count($rows) === 2, 'Direct and participant links not duplicated');
$fields = $data;
unset($fields['person_id']);
$model->saveFor($owner, $fields);
$ownedNote = (int) $model->getInsertID();
$model->addParticipant($ownedNote, $person, $owner);
verifyNote(count((new App\Models\NoteModel($db))->forPerson($person, $owner)->findAll()) === 3, 'Participant-only note included');
$model->saveFor($stranger, $fields);
$privateNote = (int) $model->getInsertID();
$db->table('note_participants')->insert(['note_id' => $privateNote, 'person_id' => $person]);
$rows = (new App\Models\NoteModel($db))->forPerson($person, $owner)->findAll();
verifyNote(!in_array($privateNote, array_column($rows, 'id')), 'Other user private notes excluded');
deniedNote(fn () => (new App\Models\NoteModel($db))->forPerson($person, $stranger));
echo "Person related notes checks passed.\n";
