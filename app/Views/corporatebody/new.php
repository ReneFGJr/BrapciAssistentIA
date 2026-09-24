<?php $editing = $editing ?? false; ?>
<section class="corporatebody-page col-12 p-3 text-light">
    <?= view('person/messages', ['inFooter' => true]) ?>
    <a href="<?= site_url('corporatebody') ?>" class="btn btn-outline-light mb-3"><i class="bi bi-arrow-left" aria-hidden="true"></i> Instituições</a>
    <h1 class="h3 mb-4"><?= $editing ? 'Editar instituição' : 'Nova instituição' ?></h1>
    <?php if (! $editing): ?>
    <form method="get" action="<?= site_url('corporatebody/new') ?>" class="card bg-dark border-secondary p-3 mb-4">
        <label for="ror-query" class="form-label">Buscar instituição no ROR</label>
        <div class="d-flex gap-2">
            <input id="ror-query" name="q" value="<?= esc($query, 'attr') ?>" class="form-control" maxlength="200" required placeholder="Nome ou sigla da instituição">
            <button type="submit" class="btn btn-info"><i class="bi bi-search" aria-hidden="true"></i> Buscar</button>
        </div>
    </form>
    <?php if ($error): ?><p class="alert alert-warning" role="alert"><?= esc($error) ?></p><?php endif; ?>
    <?php if ($result !== null): ?>
        <p><?= (int) $result['total'] ?> resultado(s). Selecione a instituição para preencher o cadastro.</p>
        <div class="list-group mb-3">
            <?php foreach ($result['items'] as $item): ?>
                <a class="list-group-item list-group-item-action bg-dark text-light border-secondary"
                    href="<?= esc(site_url('corporatebody/new') . '?' . http_build_query(['ror' => basename($item['ror_id'])]), 'attr') ?>">
                    <strong><?= esc($item['name']) ?></strong>
                    <span><?= esc(implode(' · ', array_filter([$item['acronym'], $item['city'], $item['country']]))) ?></span>
                    <small class="d-block"><?= esc($item['ror_id']) ?></small>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="d-flex gap-2 mb-4">
            <?php if ($page > 1): ?><a class="btn btn-outline-light" href="<?= esc(site_url('corporatebody/new') . '?' . http_build_query(['q' => $query, 'page' => $page - 1]), 'attr') ?>">Anterior</a><?php endif; ?>
            <?php if ($page < 500 && $page * 20 < $result['total']): ?><a class="btn btn-outline-light" href="<?= esc(site_url('corporatebody/new') . '?' . http_build_query(['q' => $query, 'page' => $page + 1]), 'attr') ?>">Próxima</a><?php endif; ?>
        </div>
    <?php endif; ?>
    <?php endif; ?>
    <form method="post" action="<?= site_url($editing ? 'corporatebody/' . $prefill['id'] . '/update' : 'corporatebody') ?>" class="card bg-dark border-secondary p-3">
        <?= csrf_field() ?>
        <h2 class="h5">Dados da instituição</h2>
        <p>Confira os dados do ROR ou preencha manualmente.</p>
        <div class="row g-3">
            <?php foreach ([
                'name' => ['Nome', 255], 'acronym' => ['Sigla', 100], 'ror_id' => ['ROR (https://ror.org/...)', 50],
                'address' => ['Endereço', 10000], 'city' => ['Cidade', 150], 'state' => ['Estado', 150],
                'country' => ['País', 150], 'country_code' => ['Código do país (BR, PT...)', 2],
                'latitude' => ['Latitude', 20], 'longitude' => ['Longitude', 20], 'established_year' => ['Ano de fundação', 4],
            ] as $field => [$label, $length]): ?>
                <div class="<?= in_array($field, ['name', 'address'], true) ? 'col-12' : 'col-md-6' ?>">
                    <label for="institution-<?= $field ?>" class="form-label"><?= esc($label) ?></label>
                    <input id="institution-<?= $field ?>" name="<?= $field ?>" class="form-control" type="text"
                        maxlength="<?= $length ?>" value="<?= esc(old($field, $prefill[$field] ?? '', false), 'attr') ?>" <?= $field === 'name' ? 'required' : '' ?>>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-3 d-flex gap-2">
            <button type="submit" class="btn btn-info">Salvar instituição</button>
            <a href="<?= site_url('corporatebody') ?>" class="btn btn-outline-light">Cancelar</a>
        </div>
    </form>
</section>