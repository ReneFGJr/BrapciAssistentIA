<?= view('header/header');?>

<body<?php if (session('logged_in') === true): ?> data-session-status-url="<?= esc(site_url('auth/status'), 'attr') ?>" data-session-home-url="<?= esc(base_url('/'), 'attr') ?>"<?php endif; ?>>

    <div class="astra-shell">

        <?= view('header/sidebar.php') ?>

        <main class="astra-main">

            <?= view('header/navbar'); ?>

            <div class="container-fluid px-0">
                <div class="row g-3">
                    <?= $content; ?>
                </div>
            </div>

            <?= view('header/footer'); ?>

        </main>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= base_url('assets/js/assistente.js') ?>?v=<?= (int) filemtime(FCPATH . 'assets/js/assistente.js') ?>"></script>
    <script src="<?= base_url('assets/js/system.js') ?>?v=<?= (int) filemtime(FCPATH . 'assets/js/system.js') ?>"></script>

</body>

</html>
