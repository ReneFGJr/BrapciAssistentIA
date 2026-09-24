<?php
$fields = [
    'nickname' => ['Nome (apelido)', 150, 'text'],
    'full_name' => ['Nome completo', 255, 'text'],
    'cpf' => ['CPF (opcional)', 14, 'text'],
    'institution_id' => ['Instituição de vínculo', 0, 'select'],
    'phone_1' => ['Telefone Móvel', 30, 'tel'],
    'phone_2' => ['Fixo', 30, 'tel'],
    'email_1' => ['E-mail 1', 254, 'email'],
    'email_2' => ['E-mail 2', 254, 'email'],
];
$action = $person === null ? 'person' : 'person/' . $person['id'] . '/update';
?>
<section class="person-page col-12 p-3 text-light">
    <h1 class="h3 mb-4"><?= $person === null ? 'Nova pessoa' : 'Editar pessoa' ?></h1>
    <?= view('person/messages') ?>
    <?php if (! empty($lookupMessage)): ?><p class="alert alert-info"><?= esc($lookupMessage) ?></p><?php endif; ?>
    <?php if ($person === null): ?><a href="<?= site_url('person/new') ?>" class="btn btn-outline-light mb-3">Buscar outra pessoa</a><?php endif; ?>
    <form action="<?= site_url($action) ?>" method="post" class="card bg-dark border-secondary">
        <?= csrf_field() ?>
        <div class="card-body person-form-grid">
            <?php foreach ($fields as $field => [$label, $length, $type]): ?>
                <div class="person-form-field">
                    <label for="<?= $field ?>" class="form-label"><?= esc($label) ?></label>
                    <?php if ($field === 'institution_id'): ?>
                        <?php $selectedInstitution = (string) old('institution_id', $person['institution_id'] ?? $prefill['institution_id'] ?? '', false); ?>
                        <select id="institution_id" name="institution_id" class="form-select">
                            <option value="">Sem vínculo institucional</option>
                            <?php foreach (($institutions ?? []) as $institution): ?>
                                <option value="<?= (int) $institution['id'] ?>" <?= $selectedInstitution === (string) $institution['id'] ? 'selected' : '' ?>>
                                    <?= esc($institution['name'] . (! empty($institution['acronym']) ? ' (' . $institution['acronym'] . ')' : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (! empty($prefill['institution']) && empty($prefill['institution_id'])): ?>
                            <small>Instituição informada na origem: <?= esc($prefill['institution']) ?>. Selecione o vínculo correspondente.</small>
                        <?php endif; ?>
                    <?php else: ?>
                    <input id="<?= $field ?>" name="<?= $field ?>" type="<?= $type ?>" class="form-control"
                        <?= $type === 'tel' ? 'data-phone-mask inputmode="tel" placeholder="+55 (41) 98811.9061"' : '' ?>
                        <?= $field === 'cpf' ? 'data-cpf-mask inputmode="numeric" placeholder="000.000.000-00"' : '' ?>
                        maxlength="<?= $length ?>" value="<?= old($field, $person[$field] ?? $prefill[$field] ?? '', 'attr') ?>"
                        <?= in_array($field, ['nickname', 'full_name'], true) ? 'required' : '' ?>>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="person-form-actions d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-info">Salvar</button>
                <a href="<?= site_url($person === null ? 'person' : 'person/' . $person['id']) ?>" class="btn btn-outline-light">Cancelar</a>
            </div>
        </div>
    </form>
</section>
<script src="<?= base_url('assets/js/person-phone.js') ?>" defer></script>
<script src="<?= base_url('assets/js/person-cpf.js') ?>" defer></script>