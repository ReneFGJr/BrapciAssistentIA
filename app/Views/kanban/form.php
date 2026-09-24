<section class="kanban-page col-12 p-3 text-light">
    <?= view('person/messages', ['inFooter' => true]) ?>
    <h1 class="h3 mb-4"><?= $item === null ? 'Novo cartão' : 'Editar cartão' ?></h1>
    <form method="post" action="<?= site_url($item === null ? 'kanban' : 'kanban/' . $item['id'] . '/update') ?>" class="card bg-dark border-secondary p-3">
        <?= csrf_field() ?>
        <label for="kanban-title" class="form-label">Título</label>
        <input id="kanban-title" name="title" class="form-control mb-3" maxlength="150" required value="<?= esc(old('title', $item['title'] ?? '', false), 'attr') ?>">
        <label for="kanban-description" class="form-label">Descrição</label>
        <textarea id="kanban-description" name="description" class="form-control mb-3" rows="5" maxlength="10000"><?= esc(old('description', $item['description'] ?? '', false)) ?></textarea>
        <div class="row g-3">
            <?php foreach (['status' => ['Status', \App\Models\KanbanModel::STATUSES, 'todo'], 'priority' => ['Prioridade', \App\Models\KanbanModel::PRIORITIES, 'normal']] as $field => [$label, $options, $default]): ?>
                <div class="col-md-6">
                    <label for="kanban-<?= $field ?>" class="form-label"><?= esc($label) ?></label>
                    <select id="kanban-<?= $field ?>" name="<?= $field ?>" class="form-select" required>
                        <?php foreach ($options as $value => $text): ?>
                            <option value="<?= esc($value, 'attr') ?>" <?= old($field, $item[$field] ?? $default, false) === $value ? 'selected' : '' ?>><?= esc($text) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-info" type="submit">Salvar</button>
            <a class="btn btn-outline-light" href="<?= site_url('kanban') ?>">Cancelar</a>
        </div>
    </form>
</section>