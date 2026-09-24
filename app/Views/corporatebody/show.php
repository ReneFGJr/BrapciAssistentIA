<section class="corporatebody-page col-12 p-3 text-light">
    <?= view('person/messages', ['inFooter' => true]) ?>
    <a class="btn btn-outline-light mb-3" href="<?= site_url('corporatebody') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Instituições</a>
    <div class="d-flex align-items-center gap-3 mb-4">
        <h1 class="h3 mb-0"><?= esc($institution['name']) ?></h1>
        <a class="btn btn-outline-info" href="<?= site_url('corporatebody/' . $institution['id'] . '/edit') ?>" title="Editar instituição" aria-label="Editar instituição"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
    </div>
    <dl class="row card-body bg-dark rounded">
        <?php foreach ([
            'acronym' => 'Sigla', 'ror_id' => 'ROR', 'address' => 'Endereço', 'city' => 'Cidade',
            'state' => 'Estado', 'country' => 'País', 'country_code' => 'Código do país',
            'latitude' => 'Latitude', 'longitude' => 'Longitude', 'established_year' => 'Ano de fundação',
        ] as $field => $label): ?>
            <div class="col-md-6 mb-3">
                <dt><?= esc($label) ?></dt>
                <dd><?= esc(($institution[$field] ?? '') !== '' && $institution[$field] !== null ? $institution[$field] : '-') ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
</section>