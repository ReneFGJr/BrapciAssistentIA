<?php
$groups = [
    ['title' => 'Dados pessoais', 'icon' => 'bi-person-vcard', 'fields' => [
        'full_name' => 'Nome completo', 'cpf' => 'CPF', 'institution' => 'Instituição de vínculo',
    ]],
    ['title' => 'Contato', 'icon' => 'bi-telephone', 'fields' => [
        'phone_1' => 'Telefone Móvel', 'phone_2' => 'Fixo',
        'email_1' => 'E-mail 1', 'email_2' => 'E-mail 2',
    ]],
];
?>
<section class="person-page person-details col-12 p-3 text-light">
    <a href="<?= site_url('person') ?>" class="person-back"><i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar para pessoas</a>
    <header class="person-detail-heading">
        <div class="person-heading-name">
            <span class="person-eyebrow">Nome (apelido)</span>
            <div class="person-name-line">
                <h1 class="h3 mb-0"><?= esc($person['nickname']) ?></h1>
                <?php if ($canEdit): ?>
                    <a href="<?= site_url('person/' . $person['id'] . '/edit') ?>" class="btn btn-outline-info person-edit-icon"
                        aria-label="Editar cadastro" title="Editar cadastro">
                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($isOwner): ?>
            <button type="button" class="btn btn-outline-info person-share-toggle" data-bs-toggle="collapse"
                data-bs-target="#person-sharing-panel" aria-expanded="false" aria-controls="person-sharing-panel"
                aria-label="Compartilhar cadastro" title="Compartilhar cadastro">
                <i class="bi bi-share" aria-hidden="true"></i>
            </button>
        <?php endif; ?>
    </header>
    <?= view('person/messages') ?>
    <?php if ($isOwner): ?>
        <div class="collapse" id="person-sharing-panel">
            <?= view('person/shares', ['person' => $person, 'shares' => $shares]) ?>
        </div>
    <?php endif; ?>
    <div class="person-data-toolbar">
        <h2 class="h5 mb-0">Dados do cadastro</h2>

    </div>
    <div class="person-data-grid">
        <?php foreach ($groups as $group): ?>
            <section class="card bg-dark border-secondary person-data-card">
                <div class="card-body">
                    <h3 class="h5"><i class="bi <?= esc($group['icon'], 'attr') ?>" aria-hidden="true"></i> <?= esc($group['title']) ?></h3>
                    <dl class="person-data-fields">
                        <?php foreach ($group['fields'] as $field => $label): ?>
                            <div class="person-data-field<?= $field === 'full_name' ? ' person-field-wide' : '' ?>">
                                <dt><?= esc($label) ?></dt>
                                <dd><?= esc(($person[$field] ?? '') !== '' ? $person[$field] : 'Não informado') ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</section>