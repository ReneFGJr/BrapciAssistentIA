<section class="col-12 p-3 text-light">
    <h1 class="h3 mb-4"><?= $note === null ? 'Novas notas' : 'Editar notas' ?></h1>
    <?= view('person/messages', ['inFooter' => true]) ?>
    <form method="post" action="<?= site_url($note === null ? 'notes/add' : 'notes/' . $note['id'] . '/update') ?>" class="card bg-dark text-light border-secondary p-3" id="notes-form">
        <?= csrf_field() ?>
        <div class="row g-3 mb-3">
            <div class="col-md-4">
        <label for="notes-date" class="form-label">Data da reunião</label>
        <input type="date" id="notes-date" name="meeting_date" class="form-control" required value="<?= esc(old('meeting_date', $note['meeting_date'] ?? date('Y-m-d'), false), 'attr') ?>">
            </div>
            <div class="col-md-4">
        <label for="notes-time" class="form-label">Hora da reunião</label>
        <input type="time" id="notes-time" name="meeting_time" class="form-control" value="<?= esc(old('meeting_time', substr($note['meeting_time'] ?? '', 0, 5), false), 'attr') ?>">
            </div>
            <div class="col-md-4">
        <label for="notes-status" class="form-label">Situação da reunião</label>
        <select id="notes-status" name="status" class="form-select" required>
            <?php foreach (\App\Models\NoteModel::STATUSES as $value => $label): ?>
                <option value="<?= esc($value, 'attr') ?>" <?= old('status', $note['status'] ?? 'scheduled', false) === $value ? 'selected' : '' ?>><?= esc($label) ?></option>
            <?php endforeach; ?>
        </select>
            </div>
        </div>
        <label for="notes-title" class="form-label">Título da reunião</label>
        <input id="notes-title" name="title" class="form-control mb-3" required maxlength="255" value="<?= esc(old('title', $note['title'] ?? '', false), 'attr') ?>">
        <label for="notes-description" class="form-label">Descrição da reunião</label>
        <textarea id="notes-description" name="description" class="form-control mb-3" rows="7" required maxlength="10000"><?= esc(old('description', $note['description'] ?? '', false)) ?></textarea>
        <div class="d-flex gap-2"><button type="submit" class="btn btn-info">Salvar</button><a class="btn btn-outline-light" href="<?= site_url('notes') ?>">Cancelar</a></div>
    </form>
    <?php if ($note !== null): ?>
        <div class="row g-3">
            <div class="col-lg-6">
                <?= view('notes/participants', ['note' => $note, 'participants' => $participants ?? [], 'availableParticipants' => $availableParticipants ?? []]) ?>
            </div>
            <div class="col-lg-6">
                <?= view('notes/categories', ['note' => $note, 'categories' => $categories ?? [], 'availableCategories' => $availableCategories ?? []]) ?>
            </div>
        </div>
        <?= view('notes/kanban', ['note' => $note, 'tasks' => $tasks ?? []]) ?>
    <?php else: ?>
        <p class="mt-3">Após salvar, use Editar para incluir os participantes da reunião.</p>
    <?php endif; ?>
</section>
