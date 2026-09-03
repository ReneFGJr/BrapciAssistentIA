<!-- =========================================================
    BARRA LATERAL
========================================================== -->
<?php
$menu = [
    ['name' => 'Home', 'url' => base_url(), 'class' => 'bi-house-fill'],
    ['name' => 'Dashboard', 'url' => base_url('dashboard'), 'class' => 'bi-grid-fill'],
    ['name' => 'Configurações', 'url' => base_url(), 'class' => 'bi-graph-up'],
    ['name' => 'Produtos', 'url' => base_url(), 'class' => 'bi-box-fill'],
    ['name' => 'Vendas', 'url' => base_url(), 'class' => 'bi-box-arrow-in-up'],
    ['name' => 'Anotações', 'url' => base_url('notepad'), 'class' => 'bi-journal-text'],
    ['name' => 'Chat', 'url' => base_url('chat'), 'class' => 'bi-chat-dots-fill'],
    ['name' => 'Setup', 'url' => base_url(), 'class' => 'bi-gear-fill'],
];
$user = session('auth_user');
$allowedAdminIds = array_filter(array_map('trim', explode(',', (string) env('admin.allowedUserIds', ''))));
$canAdminister = is_array($user) && (
    filter_var($user['admin'] ?? false, FILTER_VALIDATE_BOOL)
    || in_array((string) ($user['id'] ?? ''), $allowedAdminIds, true)
);
if ($canAdminister) {
    $menu[] = ['name' => 'Administrar aplicativos', 'url' => base_url('dashboard/admin'), 'class' => 'bi-shield-lock-fill'];
}
?>
<aside class="astra-sidebar">
<?php if (session('logged_in') === true): ?>
<?php foreach ($menu as $item): ?><a href="<?= esc($item['url'], 'attr') ?>" class="sidebar-button" title="<?= esc($item['name'], 'attr') ?>"><i class="bi <?= esc($item['class'], 'attr') ?>"></i></a><?php endforeach; ?>
<div class="sidebar-spacer"></div><a href="<?= base_url() ?>" class="sidebar-button" title="Ajuda"><i class="bi bi-question-circle"></i></a>
<?php endif; ?>
</aside>
