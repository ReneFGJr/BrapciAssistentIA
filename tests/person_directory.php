<?php
require __DIR__ . '/notes.php';
$guest = $service->create(['nickname' => 'Nilda', 'full_name' => 'Nilda Jacks'], $stranger);
$db->table('note_participants')->insert(['note_id' => $id, 'person_id' => $guest]);
$search = fn (array $actor, string $query) => (new App\Models\PersonModel($db))
    ->visibleInDirectory($actor)->searchByWords($query)->findAll();
verifyNote($search($owner, 'nilda') === [], 'Meeting participation does not grant Person access');
verifyNote(count($search($stranger, 'nilda')) === 1, 'Owner can search own Person');
$service->share($guest, $stranger, $owner['email'], 'read', '');
verifyNote(count($search($owner, 'nilda')) === 1, 'Active read grant included');
$db->table('persons_user')->where('person_id', $guest)->where('user', 'owner')
    ->update(['expires_at' => '2000-01-01 00:00:00']);
verifyNote($search($owner, 'nilda') === [], 'Expired grant excluded');
verifyNote(!in_array($guest, array_column($search($owner, ''), 'id')), 'Empty search respects grants');
$db->table('persons_user')->where('person_id', $guest)->where('user', 'owner')
    ->update(['expires_at' => null, 'access_level' => 'edit']);
verifyNote(count($search($owner, 'Jacks Nilda')) === 1, 'Active edit grant and word search');
$db->table('persons_user')->where('person_id', $guest)->where('user', 'owner')->delete();
verifyNote($search($owner, 'nilda') === [], 'Revoked grant excluded');
echo "Person directory user access checks passed.\n";
