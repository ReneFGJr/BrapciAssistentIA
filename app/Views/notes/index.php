<section class="col-12 p-3 text-light">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Notas de reuniões</h1>
        <a class="btn btn-info" href="<?= site_url('notes/add') ?>">Novas notas</a>
    </div>
    <?= view('person/messages', ['inFooter' => true]) ?>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle">
            <thead><tr><th>Data da reunião</th><th>Hora</th><th>Participantes</th><th>Título</th><th>Descrição</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
            <?php if ($notes === []): ?><tr><td colspan="7">Nenhuma reunião registrada.</td></tr><?php endif; ?>
            <?php foreach ($notes as $note): ?>
                <tr>
                    <td class="text-nowrap"><?= esc(date('d/m/Y', strtotime($note['meeting_date']))) ?></td>
                    <td><?= empty($note['meeting_time']) ? 'Não informada' : esc(substr($note['meeting_time'], 0, 5)) ?></td>

                    <td>
                        <?php $participants = $participantsByNote[$note['id']] ?? []; ?>
                        <?php if ($participants === []): ?>Nenhum participante<?php else: ?>
                            <ul class="mb-0 ps-3"><?php foreach ($participants as $participant): ?><li><?= esc($participant['full_name']) ?></li><?php endforeach; ?></ul>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($note['title']) ?></td>
                    <td style="min-width: 220px; overflow-wrap: anywhere;"><?= nl2br(esc($note['description'])) ?></td>
                    <td><?= esc(\App\Models\NoteModel::STATUSES[$note['status']] ?? $note['status']) ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-info" href="<?= site_url('notes/' . $note['id']) ?>" title="Visualizar nota" aria-label="<?= esc('Visualizar ' . $note['title'], 'attr') ?>"><i class="bi bi-eye" aria-hidden="true"></i></a>
                        <?php if (((!empty($note['user_id']) && (string) $note['user_id'] === ($actorId ?? '')) || in_array($note['person_id'], $editableIds))): ?>
                            <a class="btn btn-sm btn-outline-info" href="<?= site_url('notes/' . $note['id'] . '/edit') ?>" title="Editar nota" aria-label="<?= esc('Editar ' . $note['title'], 'attr') ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $pager->links() ?>
</section>
