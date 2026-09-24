<section class="corporatebody-page col-12 p-3 text-light">
    <?= view('person/messages', ['inFooter' => true]) ?>
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
        <h1 class="h3 mb-0">Instituições</h1>
        <a class="btn btn-info" href="<?= site_url('corporatebody/new') ?>"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nova instituição</a>
    </div>
    <div class="table-responsive">
        <table class="table table-dark table-hover">
            <thead><tr><th>Instituição</th><th>Sigla</th><th>Cidade</th><th>País</th><th>ROR</th><th class="text-end">Ações</th></tr></thead>
            <tbody>
                <?php if ($institutions === []): ?><tr><td colspan="6">Nenhuma instituição cadastrada.</td></tr><?php endif; ?>
                <?php foreach ($institutions as $institution): ?>
                    <tr>
                        <?php foreach (['name', 'acronym', 'city', 'country'] as $field): ?>
                            <td><?= esc($institution[$field] ?: '-') ?></td>
                        <?php endforeach; ?>
                        <td>
                            <?php if (preg_match('/^https:\/\/ror\.org\/0[0-9a-hj-km-np-tv-z]{6}[0-9]{2}$/', $institution['ror_id'] ?? '')): ?>
                                <a href="<?= esc($institution['ror_id'], 'attr') ?>" target="_blank" rel="noopener noreferrer"><?= esc($institution['ror_id']) ?></a>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-info" href="<?= site_url('corporatebody/' . $institution['id']) ?>" title="Visualizar instituição" aria-label="Visualizar instituição"><i class="bi bi-eye" aria-hidden="true"></i></a>
                            <a class="btn btn-sm btn-outline-info" href="<?= site_url('corporatebody/' . $institution['id'] . '/edit') ?>" title="Editar instituição" aria-label="Editar instituição"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $pager->links() ?>
</section>