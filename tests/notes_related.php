<?php
require __DIR__ . '/note_categories.php';
$subjects->attach($id, (int) $category['id'], $owner);
$model->saveFor($owner, $data);
$relatedId = (int) $model->getInsertID();
$subjects->attach($relatedId, (int) $category['id'], $owner);
$related = $subjects->relatedNotes((int) $category['id'], $id, $owner)->findAll();
verifyNote(count($related) === 2 && in_array($id, array_column($related, 'id')) && in_array($relatedId, array_column($related, 'id')), 'All linked notes including source');
deniedNote(fn () => $subjects->relatedNotes((int) $category['id'], $id, $stranger));
$otherCategory = $subjects->forUser($reader)[0];
deniedNote(fn () => $subjects->relatedNotes((int) $otherCategory['id'], $id, $owner));
$db->table('notes')->where('id', $relatedId)->update(['person_id' => $other]);
$remaining = $subjects->relatedNotes((int) $category['id'], $id, $owner)->findAll();
verifyNote(count($remaining) === 1 && (int) $remaining[0]['id'] === $id, 'Source remains visible and inaccessible note omitted');
$html = view('notes/show', [
    'note' => $model->visibleTo($owner)->where('notes.id', $id)->first(),
    'canEdit' => true, 'participants' => [], 'categories' => [$category],
    'tasks' => [['id' => 1, 'title' => '<script>task</script>', 'description' => 'Descrição', 'status' => 'todo', 'priority' => 'normal']],
]);
verifyNote(str_contains($html, '/subjects/') && str_contains($html, 'Pesquisa'), 'Category link shown');
verifyNote(str_contains($html, '&lt;script&gt;task&lt;/script&gt;') && !str_contains($html, '<script>task</script>'), 'Task shown escaped');
echo "Related notes and detail checks passed.\n";
