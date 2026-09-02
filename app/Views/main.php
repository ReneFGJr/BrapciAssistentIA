<?= view('header/header');?>

<body>

    <div class="astra-shell">

        <?= view('header/sidebar.php') ?>


        <!-- =========================================================
         CONTEÚDO PRINCIPAL
    ========================================================== -->
        <main class="astra-main">

            <?= view('header/navbar'); ?>


            <!-- =====================================================
             GRID PRINCIPAL
        ====================================================== -->
            <div class="container-fluid px-0">

                <div class="row g-3">

                  <?= $content; ?>

                </div>

            </div>

            <?= view('header/footer'); ?>

        </main>

    </div>


    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
    </script>

    <script src="assets/js/assistente.js"></script>

</body>

</html>