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
            <button type="button" class="btn btn-outline-info" data-bs-toggle="collapse" data-bs-target="#person-import-panel" aria-expanded="false" aria-controls="person-import-panel" title="Importar arquivo CSV"><i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i> Importar</button>
        </div>
    </form>
    <div class="collapse mb-4" id="person-import-panel">
        <form id="person-import" action="<?= site_url('person/import') ?>" method="post" enctype="multipart/form-data" class="card bg-dark border-secondary p-3">
            <?= csrf_field() ?>
            <label for="contacts-csv" class="form-label">Arquivo de contatos</label>
            <input id="contacts-csv" type="file" name="contacts_csv" class="form-control" accept=".csv,text/csv" required aria-describedby="contacts-csv-help">
            <small id="contacts-csv-help" class="d-block mt-2">Selecione um CSV de contatos Google em UTF-8, de até 5 MB. Para continuar fotos pendentes, envie o mesmo arquivo novamente.</small>
            <div class="mt-3">
                <button type="submit" class="btn btn-info"><i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i> Importar arquivo</button>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle">
            <thead><tr><th scope="col">Nome (apelido)</th><th scope="col">Nome completo</th><th scope="col">Celular</th><th scope="col" class="text-end">Ações</th></tr></thead>
            <tbody>
                <?php if ($persons === []): ?>
                    <tr><td colspan="4"><?= $search !== '' ? 'Nenhuma pessoa encontrada para esta busca.' : 'Nenhuma pessoa cadastrada.' ?></td></tr>
                <?php endif; ?>
                <?php foreach ($persons as $person): ?>
                    <tr>
                        <td>
                            <div class="person-list-name">
                                <?php if (preg_match('/^[a-f0-9]{32}\.jpg$/', $person['photo'] ?? '')): ?>
                                    <img class="person-list-photo" src="<?= esc(base_url('repository/photo/' . $person['photo']), 'attr') ?>" alt="" width="48" height="48" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <span class="person-list-photo person-list-photo-placeholder" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                                <?php endif; ?>
                                <span><?= esc($person['nickname']) ?></span>
                            </div>
                        </td>
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