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
    <?= view('person/messages', ['inFooter' => true]) ?>
    <?php if ($isOwner): ?>
        <div class="collapse" id="person-sharing-panel">
            <?= view('person/shares', ['person' => $person, 'shares' => $shares]) ?>
        </div>
    <?php endif; ?>
    <div class="person-data-grid">
        <?php foreach ($groups as $group): ?>
            <section class="card bg-dark border-secondary person-data-card">
                <div class="card-body">
                    <?php if (isset($group['fields']['full_name'])): ?>
                        <aside class="person-photo-panel">
                            <div class="person-avatar">
                                <?php if (preg_match('/^[a-f0-9]{32}\.jpg$/', $person['photo'] ?? '')): ?>
                                    <img class="person-photo" src="<?= base_url('repository/photo/' . $person['photo']) ?>" alt="<?= esc('Fotografia de ' . $person['nickname'], 'attr') ?>" width="260" height="260">
                                <?php else: ?>
                                    <div class="person-photo person-photo-placeholder" role="img" aria-label="Sem fotografia">
                                        <svg viewBox="0 0 260 260" aria-hidden="true" focusable="false">
                                            <defs><linearGradient id="person-avatar-colors"><stop offset="50%" stop-color="#c7cbce"/><stop offset="50%" stop-color="#ffd1f3"/></linearGradient></defs>
                                            <path fill="url(#person-avatar-colors)" d="M25 260v-18c0-16 20-22 48-31 20-7 27-14 27-30v-13c-10-10-16-24-18-40-7 0-10-9-10-19 0-7 2-10 6-10-5-28-3-48 12-57 4-15 23-21 40-21 34 0 55 23 53 61l-2 17c5 0 7 4 7 10 0 10-4 19-10 19-2 16-8 30-18 40v13c0 16 7 23 27 30 28 9 48 15 48 31v18z"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                <?php if ($canEdit): ?>
                                    <form action="<?= site_url('person/' . $person['id'] . '/photo') ?>" method="post" enctype="multipart/form-data" data-photo-form>
                                        <?= csrf_field() ?>
                                        <input id="person-photo-upload" type="file" name="photo" accept="image/jpeg,image/png,image/webp" hidden required>
                                        <button class="person-photo-camera" type="button" data-photo-picker aria-label="Alterar fotografia" title="Alterar fotografia">
                                            <i class="bi bi-camera-fill" aria-hidden="true"></i>
                                        </button>
                                        <noscript><style>#person-photo-upload { display: block !important; } .person-photo-camera { display: none; }</style><button type="submit" class="btn btn-info mt-2">Salvar foto</button></noscript>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <span class="person-photo-status small" data-photo-status role="status" aria-live="polite"></span>
                        </aside>
                    <?php endif; ?>
                    <h3 class="h5"><i class="bi <?= esc($group['icon'], 'attr') ?>" aria-hidden="true"></i> <?= esc($group['title']) ?></h3>
                    <dl class="person-data-fields">
                        <?php foreach ($group['fields'] as $field => $label): ?>
                            <div class="person-data-field<?= $field === 'full_name' ? ' person-field-wide' : '' ?>">
                                <dt><?= esc($label) ?></dt>
                                <?php $value = trim((string) ($person[$field] ?? '')); ?>
                                <dd>
                                    <?= esc($value !== '' ? $value : '-') ?>
                                    <?php if ($value !== '' && str_starts_with($field, 'email_')): ?>
                                        <button type="button" class="btn btn-sm btn-outline-info ms-1" data-copy-email="<?= esc($value, 'attr') ?>" aria-label="Copiar e-mail" title="Copiar e-mail"><i class="bi bi-clipboard" aria-hidden="true"></i></button>
                                    <?php elseif ($value !== '' && str_starts_with($field, 'phone_') && ($whatsapp = \App\Libraries\PersonPhoto::whatsapp($value)) !== null): ?>
                                        <a class="btn btn-sm person-whatsapp ms-1" href="https://wa.me/<?= esc($whatsapp, 'attr') ?>" target="_blank" rel="noopener noreferrer" aria-label="Abrir conversa no WhatsApp" title="Abrir WhatsApp"><i class="bi bi-whatsapp" aria-hidden="true"></i></a>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
    <section class="card bg-dark text-light border-secondary p-3 mt-4" aria-labelledby="person-notes-title">
        <h2 id="person-notes-title" class="h5">Notas relacionadas</h2>
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle">
                <thead><tr><th>Data</th><th>Hora</th><th>Título</th><th>Situação</th><th>Ações</th></tr></thead>
                <tbody>
                    <?php if (empty($notes)): ?><tr><td colspan="5">Nenhuma nota relacionada disponível.</td></tr><?php endif; ?>
                    <?php foreach ($notes ?? [] as $note): ?><tr>
                        <td><?= esc(date('d/m/Y', strtotime($note['meeting_date']))) ?></td>
                        <td><?= empty($note['meeting_time']) ? 'Não informada' : esc(substr($note['meeting_time'], 0, 5)) ?></td>
                        <td><a class="link-info" href="<?= site_url('notes/' . $note['id']) ?>"><?= esc($note['title']) ?></a></td>
                        <td><?= esc(\App\Models\NoteModel::STATUSES[$note['status']] ?? $note['status']) ?></td>
                        <td><a class="btn btn-sm btn-outline-info" href="<?= site_url('notes/' . $note['id']) ?>" title="Visualizar nota" aria-label="<?= esc('Visualizar ' . $note['title'], 'attr') ?>"><i class="bi bi-eye" aria-hidden="true"></i></a></td>
                    </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= isset($notesPager) ? $notesPager->links('person_notes') : '' ?>
    </section>
</section>
<div id="person-copy-status" class="visually-hidden" role="status" aria-live="polite"></div>
<script src="<?= base_url('assets/js/person-details.js') ?>" defer></script>