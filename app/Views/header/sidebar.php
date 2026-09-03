<!-- =========================================================
    BARRA LATERAL
========================================================== -->
<?php
$menu = [];
$menu[] = ['name' => 'Home', 'url' => base_url(), 'class' => 'bi-house-fill'];
$menu[] = ['name' => 'Dashboard', 'url' => base_url(), 'class' => 'bi-grid-fill'];
$menu[] = ['name' => 'Configurações', 'url' => base_url(), 'class' => 'bi-graph-up'];
$menu[] = ['name' => 'Produtos', 'url' => base_url(), 'class' => 'bi-box-fill'];
$menu[] = ['name' => 'Vendas', 'url' => base_url(), 'class' => 'bi-box-arrow-in-up'];
$menu[] = ['name' => 'Setup', 'url' => base_url(), 'class' => 'bi-gear-fill'];
?>
<aside class="astra-sidebar">
    <?php if (session('logged_in') === true): ?>
        <?php foreach ($menu as $item): ?>
            <a href="<?= $item['url'] ?>" class="sidebar-button">
                <i class="bi <?= $item['class'] ?>"></i>
            </a>
        <?php endforeach; ?>

        <div class="sidebar-spacer"></div>

        <a href="<?= base_url() ?>" class="sidebar-button">
            <i class="bi bi-question-circle"></i>
        </a>
    <?php endif; ?>
</aside>