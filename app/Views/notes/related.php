<section class="col-12 p-3 text-light">
    <?= view('person/messages', ['inFooter' => true]) ?>
    <div class="d-flex justify-content-between gap-3 mb-4">
        <h1 class="h3">Notas do assunto: <?= esc($category['name']) ?></h1>
        <a class="btn btn-outline-light" href="<?= site_url('notes/' . $sourceId) ?>">Voltar à nota</a>
    </div>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle">
            <thead><tr><th>Data</th><th>Hora</th><th>Título</th><th>Situação</th><th>Ações</th></tr></thead>
            <tbody>
                <?php if ($notes === []): ?><tr><td colspan="5">Nenhuma nota relacionada a este assunto.</td></tr><?php endif; ?>
                <?php foreach ($notes as $note): ?><tr>
                    <td><?= esc(date('d/m/Y', strtotime($note['meeting_date']))) ?></td>
                    <td><?= empty($note['meeting_time']) ? 'Não informada' : esc(substr($note['meeting_time'], 0, 5)) ?></td>
                    <td><a class="link-info" href="<?= site_url('notes/' . $note['id']) ?>"><?= esc($note['title']) ?></a></td>
                    <td><?= esc(\App\Models\NoteModel::STATUSES[$note['status']]) ?></td>
                    <td><a class="btn btn-sm btn-outline-info" href="<?= site_url('notes/' . $note['id']) ?>" title="Visualizar nota" aria-label="Visualizar nota"><i class="bi bi-eye" aria-hidden="true"></i></a></td>
                </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $pager->links() ?>
</section>
