<?php
require __DIR__ . '/notes.php';
$service->create(['nickname' => 'MJ', 'full_name' => 'Maria Helena Jacks'], $owner);
$service->create(['nickname' => 'Maria', 'full_name' => 'Maria Souza'], $owner);
$rows = (new App\Models\PersonModel($db))->searchByWords('Jacks Maria')->findAll();
verifyNote(count($rows) === 1 && $rows[0]['full_name'] === 'Maria Helena Jacks', 'Words match in any order');
verifyNote(count((new App\Models\PersonModel($db))->searchByWords('Hel Jack')->findAll()) === 1, 'Partial words');
verifyNote((new App\Models\PersonModel($db))->searchByWords('Maria Missing')->findAll() === [], 'All words required');
verifyNote((new App\Models\PersonModel($db))->searchByWords('%')->findAll() === [], 'Wildcards escaped');
$html = view('notes/participants', ['note' => ['id' => $id], 'participants' => []]);
verifyNote(str_contains($html, 'participant_id') && str_contains($html, 'notes-participants.js') && !str_contains($html, 'Cadastrar e adicionar'), 'Existing Person selection');
echo "Participant autocomplete checks passed.\n";
