<section class="person-page col-12 p-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-light mb-0">Pessoas</h1>

    </div>
    <?= view('person/messages') ?>
    <form action="<?= site_url('person') ?>" method="get" class="mb-4">
        <label for="person-search" class="form-label text-light">Buscar por apelido ou nome completo</label>
        <div class="d-flex flex-wrap gap-2">
            <input id="person-search" name="q" type="search" class="form-control w-auto flex-grow-1"
                value="<?= esc($search, 'attr') ?>" placeholder="Digite um nome...">
            <button type="submit" class="btn btn-info">Buscar</button>
            <a href="<?= site_url('person') ?>" class="btn btn-outline-light">Limpar</a>
            <a href="<?= site_url('person/new') ?>" class="btn btn-info" aria-label="Cadastrar pessoa" title="Cadastrar pessoa"><i class="bi bi-plus-lg" aria-hidden="true"></i></a>
            <button type="submit" form="person-import" class="btn btn-outline-info" title="Importar contacts.csv"><i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i> Importar</button>
        </div>
    </form>
    <form id="person-import" action="<?= site_url('person/import') ?>" method="post">
        <?= csrf_field() ?>
    </form>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle">
            <thead><tr><th scope="col">Nome (apelido)</th><th scope="col">Nome completo</th><th scope="col">Celular</th><th scope="col" class="text-end">Ações</th></tr></thead>
            <tbody>
                <?php if ($persons === []): ?>
                    <tr><td colspan="4"><?= $search !== '' ? 'Nenhuma pessoa encontrada para esta busca.' : 'Nenhuma pessoa cadastrada.' ?></td></tr>
                <?php endif; ?>
                <?php foreach ($persons as $person): ?>
                    <tr>
                        <td><?= esc($person['nickname']) ?></td>
                        <td><?= esc($person['full_name']) ?></td>
                        <td><?= esc(trim((string) ($person['phone_1'] ?? '')) ?: '-') ?></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-info" href="<?= site_url('person/' . $person['id']) ?>"
                            aria-label="<?= esc('Visualizar cadastro de ' . $person['nickname'], 'attr') ?>">Visualizar</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?= $pager->only(['q'])->links() ?>
</section>