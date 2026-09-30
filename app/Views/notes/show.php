<?php
$linkify = static function (string $text): string {
    $parts = preg_split('~(https?://[^\s<>"\']+)~iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    if ($parts === false) return esc($text);
    $html = '';
    foreach ($parts as $part) {
        if (preg_match('~^https?://~iu', $part) === 1 && filter_var($part, FILTER_VALIDATE_URL)) {
            $html .= '<a class="link-info" href="' . esc($part, 'attr') . '" target="_blank" rel="noopener noreferrer">' . esc($part) . '</a>';
        } else {
            $html .= esc($part);
        }
    }
    return $html;
};
?>
<section class="col-12 p-3 text-light">
    <?= view('person/messages', ['inFooter' => true]) ?>
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <h1 class="h3 mb-0"><?= esc($note['title']) ?></h1>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-light" href="<?= site_url('notes') ?>" title="Voltar às notas" aria-label="Voltar às notas"><i class="bi bi-arrow-left" aria-hidden="true"></i></a>
            <?php if ($canEdit): ?>
                <a class="btn btn-outline-info" href="<?= site_url('notes/' . $note['id'] . '/edit') ?>" title="Editar nota" aria-label="Editar nota"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card bg-dark text-light border-secondary p-3">
        <dl class="row mb-0">
            <?php if (!empty($note['person_id'])): ?>
            <dt class="col-sm-3">Person</dt>
            <dd class="col-sm-9"><a class="link-info" href="<?= site_url('person/' . $note['person_id']) ?>"><?= esc($note['person_name']) ?></a></dd>
            <?php endif; ?>
            <dt class="col-sm-3">Data da reunião</dt>
            <dd class="col-sm-9"><?= esc(date('d/m/Y', strtotime($note['meeting_date']))) ?></dd>
            <dt class="col-sm-3">Hora da reunião</dt>
            <dd class="col-sm-9"><?= empty($note['meeting_time']) ? 'Não informada' : esc(substr($note['meeting_time'], 0, 5)) ?></dd>
            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9"><?= esc(\App\Models\NoteModel::STATUSES[$note['status']] ?? $note['status']) ?></dd>
        </dl>
        <h2 class="h5 mt-3">Descrição da reunião</h2>
        <div style="white-space: pre-wrap; overflow-wrap: anywhere;"><?= $linkify((string) $note['description']) ?></div>
        <h2 class="h5 mt-4">Participantes</h2>
        <?php if ($participants === []): ?>
            <p class="mb-0">Nenhum participante incluído.</p>
        <?php else: ?>
            <ul class="mb-0"><?php foreach ($participants as $participant): ?><li><?= esc($participant['full_name']) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
    </div>
    <section class="card bg-dark text-light border-secondary p-3 mt-4">
        <h2 class="h5">Categorias / assuntos</h2>
        <?php if (empty($categories)): ?><p class="mb-0">Nenhuma categoria associada.</p><?php else: ?>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($categories as $category): ?>
                    <a class="btn btn-outline-info" href="<?= site_url('notes/' . $note['id'] . '/subjects/' . $category['id']) ?>" title="Ver notas deste assunto"><?= esc($category['name']) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <section class="card bg-dark text-light border-secondary p-3 mt-4">
        <h2 class="h5">Atividades no Kanban</h2>
        <?php if (empty($tasks)): ?><p class="mb-0">Nenhuma tarefa sua associada a esta nota.</p><?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark align-middle mb-0">
                    <thead><tr><th>Tarefa</th><th>Descrição</th><th>Situação</th><th>Prioridade</th><th>Ações</th></tr></thead>
                    <tbody><?php foreach ($tasks as $task): ?><tr>
                        <td><?= esc($task['title']) ?></td>
                        <td style="white-space: pre-wrap; overflow-wrap: anywhere;"><?= $linkify((string) $task['description']) ?></td>
                        <td><?= esc(\App\Models\KanbanModel::STATUSES[$task['status']]) ?></td>
                        <td><?= esc(\App\Models\KanbanModel::PRIORITIES[$task['priority']]) ?></td>
                        <td><a class="btn btn-sm btn-outline-info" href="<?= site_url('kanban/' . $task['id'] . '/edit') ?>" title="Editar tarefa" aria-label="Editar tarefa"><i class="bi bi-pencil-square" aria-hidden="true"></i></a></td>
                    </tr><?php endforeach; ?></tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</section>
