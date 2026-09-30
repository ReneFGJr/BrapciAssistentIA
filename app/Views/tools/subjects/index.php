<section class="col-12 p-3 text-light">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">Assuntos</h1>
        <a class="btn btn-primary" href="<?= site_url('tools/subjects/add') ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Novo assunto</a>
    </div>
    <p>Gerencie seus assuntos usados nas notas e na agenda.</p>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle">
            <thead><tr><th>Assunto</th><th class="text-end">Ações</th></tr></thead>
            <tbody>
            <?php foreach ($subjects as $subject): ?>
                <tr>
                    <td><?= esc($subject['name']) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-light" href="<?= site_url('tools/subjects/' . $subject['id'] . '/edit') ?>" title="Editar assunto" aria-label="Editar assunto"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                        <form class="d-inline" method="post" action="<?= site_url('tools/subjects/' . $subject['id'] . '/delete') ?>" onsubmit="return confirm('Excluir este assunto? Os vínculos serão removidos, mas as notas e reuniões serão preservadas.');">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger" title="Excluir assunto" aria-label="Excluir assunto"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($subjects === []): ?><tr><td colspan="2">Nenhum assunto cadastrado.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <a class="btn btn-outline-light" href="<?= site_url('tools') ?>">Voltar às ferramentas</a>
</section>
