<section class="card bg-dark text-light border-secondary p-3 mt-4" aria-labelledby="note-kanban-title">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <h2 id="note-kanban-title" class="h5 mb-0">Atividades no Kanban</h2>
        <button type="button" class="btn btn-info" data-bs-toggle="collapse" data-bs-target="#note-task-panel" aria-controls="note-task-panel" aria-expanded="<?= session()->getFlashdata('note_task_form') ? 'true' : 'false' ?>">Nova tarefa</button>
    </div>
    <?php if ($tasks === []): ?><p>Nenhuma tarefa sua associada a esta nota.</p><?php endif; ?>
    <?php if ($tasks !== []): ?>
    <div class="table-responsive">
        <table class="table table-dark align-middle">
            <thead><tr><th>Tarefa</th><th>Situação</th><th>Prioridade</th><th>Ações</th></tr></thead>
            <tbody><?php foreach ($tasks as $task): ?><tr>
                <td><?= esc($task['title']) ?></td>
                <td><?= esc(\App\Models\KanbanModel::STATUSES[$task['status']]) ?></td>
                <td><?= esc(\App\Models\KanbanModel::PRIORITIES[$task['priority']]) ?></td>
                <td><a class="btn btn-sm btn-outline-info" href="<?= site_url('kanban/' . $task['id'] . '/edit') ?>" title="Editar tarefa" aria-label="<?= esc('Editar ' . $task['title'], 'attr') ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i></a></td>
            </tr><?php endforeach; ?></tbody>
        </table>
    </div>
    <?php endif; ?>
    <div id="note-task-panel" class="collapse<?= session()->getFlashdata('note_task_form') ? ' show' : '' ?>">
        <form method="post" action="<?= site_url('notes/' . $note['id'] . '/tasks') ?>" class="border-top border-secondary pt-3">
            <?= csrf_field() ?>
            <label for="note-task-title" class="form-label">Título da tarefa</label>
            <input id="note-task-title" name="task_title" class="form-control mb-3" maxlength="150" required value="<?= esc(old('task_title', '', false), 'attr') ?>">
            <label for="note-task-description" class="form-label">Descrição</label>
            <textarea id="note-task-description" name="task_description" class="form-control mb-3" rows="4" maxlength="10000"><?= esc(old('task_description', '', false)) ?></textarea>
            <div class="row g-3">
                <?php foreach (['status' => ['Situação', \App\Models\KanbanModel::STATUSES, 'todo'], 'priority' => ['Prioridade', \App\Models\KanbanModel::PRIORITIES, 'normal']] as $field => [$label, $options, $default]): ?>
                <div class="col-md-6">
                    <label for="note-task-<?= $field ?>" class="form-label"><?= esc($label) ?></label>
                    <select id="note-task-<?= $field ?>" name="task_<?= $field ?>" class="form-select" required>
                        <?php foreach ($options as $value => $text): ?><option value="<?= esc($value, 'attr') ?>" <?= old('task_' . $field, $default, false) === $value ? 'selected' : '' ?>><?= esc($text) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-info">Adicionar ao Kanban</button>
                <button type="button" class="btn btn-outline-light" data-bs-toggle="collapse" data-bs-target="#note-task-panel">Cancelar</button>
            </div>
        </form>
    </div>
</section>
