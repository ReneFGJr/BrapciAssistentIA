<?php
require __DIR__ . '/notes.php';
require APPPATH . 'Database/Migrations/2026-09-30-000005_CreateSubjects.php';
(new App\Database\Migrations\CreateSubjects(Config\Database::forge($db)))->up();
$subjects = new App\Models\SubjectModel($db);
verifyNote($subjects->createAndAttach($id, ' Pesquisa ', $owner), 'Create and attach');
$category = $subjects->forUser($owner)[0];
verifyNote($category['user_id'] === 'owner' && $category['name'] === 'Pesquisa', 'Owner from actor and normalized name');
verifyNote($subjects->createAndAttach($id, 'Pesquisa', $owner), 'Reuse existing category');
verifyNote(count($subjects->forUser($owner)) === 1 && count($subjects->forNote($id, $owner)) === 1, 'No duplicate category or link');
verifyNote($subjects->attach($recentId, (int) $category['id'], $owner), 'Reuse on another note');
verifyNote($subjects->forUser($stranger) === [], 'Other users categories hidden');
deniedNote(fn () => $subjects->attach($id, (int) $category['id'], $stranger));
deniedNote(fn () => $subjects->createAndAttach($id, 'Blocked', $stranger));
deniedNote(fn () => $subjects->detach($id, (int) $category['id'], $stranger));
$db->table('persons_user')->where('user', 'reader')->update(['expires_at' => null, 'access_level' => 'edit']);
deniedNote(fn () => $subjects->attach($id, (int) $category['id'], $reader));
verifyNote($subjects->createAndAttach($id, 'Pesquisa', $reader), 'Same name permitted for another user');
verifyNote(count($subjects->forNote($id, $owner)) === 1 && count($subjects->forNote($id, $reader)) === 1, 'Private categories on shared note');
try {
    $subjects->createAndAttach($id, ' ', $owner);
    throw new RuntimeException('Empty name accepted');
} catch (InvalidArgumentException $exception) {}
verifyNote($subjects->detach($id, (int) $category['id'], $owner), 'Detach');
verifyNote($subjects->forNote($id, $owner) === [] && count($subjects->forNote($recentId, $owner)) === 1, 'Detach only selected note');
verifyNote(count($subjects->forUser($owner)) === 1, 'Reusable category retained');
$html = view('notes/categories', ['note' => ['id' => $id], 'categories' => [['id' => 99, 'name' => '<script>bad</script>']], 'availableCategories' => []]);
verifyNote(!str_contains($html, '<script>bad</script>') && str_contains($html, '&lt;script&gt;'), 'Category names escaped');
$model->delete($recentId);
verifyNote($db->table('note_subjects')->where('note_id', $recentId)->countAllResults() === 0, 'Deleted note links removed');
echo "Category checks passed: creation, reuse, isolation, permissions, removal and escaping.\n";
