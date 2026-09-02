<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Assistente.PHP</title>

    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"
        rel="stylesheet">

    <link href="assets/css/assistente.css" rel="stylesheet">
</head>

<body>

    <div class="astra-shell">

        <!-- =========================================================
         BARRA LATERAL
    ========================================================== -->
        <aside class="astra-sidebar">

            <a href="#" class="sidebar-button active">
                <i class="bi bi-house-fill"></i>
            </a>

            <a href="#" class="sidebar-button">
                <i class="bi bi-grid-fill"></i>
            </a>

            <a href="#" class="sidebar-button">
                <i class="bi bi-graph-up"></i>
            </a>

            <a href="#" class="sidebar-button">
                <i class="bi bi-database-fill"></i>
            </a>

            <a href="#" class="sidebar-button">
                <i class="bi bi-gear-fill"></i>
            </a>

            <div class="sidebar-spacer"></div>

            <a href="#" class="sidebar-button">
                <i class="bi bi-question-circle"></i>
            </a>

        </aside>


        <!-- =========================================================
         CONTEÚDO PRINCIPAL
    ========================================================== -->
        <main class="astra-main">

            <!-- HEADER -->
            <header class="astra-header">

                <div class="system-title">
                    <strong>ASSISTENTE.PHP</strong>
                    <span class="system-code">NCC-2026-01</span>
                    <span class="system-description">
                        SISTEMA DE CONTROLE DISTRIBUÍDO
                    </span>
                </div>

                <div class="system-status">
                    STATUS GERAL
                    <strong>OPERACIONAL</strong>
                </div>

            </header>


            <!-- =====================================================
             GRID PRINCIPAL
        ====================================================== -->
            <div class="container-fluid px-0">

                <div class="row g-3">

                    <!-- =============================================
                     COLUNA CENTRAL
                ============================================== -->
                    <div class="col-12 col-xl-9">

                        <div class="row g-3">

                            <!-- TAREFAS -->
                            <div class="col-12 col-lg-6">

                                <section class="astra-panel panel-orange">

                                    <header class="panel-header">
                                        <div>
                                            <i class="bi bi-diagram-3-fill"></i>
                                            TAREFAS EXECUTANDO
                                        </div>

                                        <div class="panel-actions">
                                            <i class="bi bi-arrows-fullscreen"></i>
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </div>
                                    </header>

                                    <div class="panel-body">

                                        <iframe
                                            src="components/tasks.php"
                                            title="Tarefas executando"
                                            loading="lazy">
                                        </iframe>

                                    </div>

                                </section>

                            </div>


                            <!-- STATUS -->
                            <div class="col-12 col-lg-6">

                                <section class="astra-panel panel-cyan">

                                    <header class="panel-header">
                                        <div>
                                            <i class="bi bi-cpu-fill"></i>
                                            STATUS DO SISTEMA
                                        </div>

                                        <div class="panel-actions">
                                            <i class="bi bi-arrows-fullscreen"></i>
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </div>
                                    </header>

                                    <div class="panel-body">

                                        <iframe
                                            src="components/status.php"
                                            title="Status do sistema"
                                            loading="lazy">
                                        </iframe>

                                    </div>

                                </section>

                            </div>


                            <!-- LOGS -->
                            <div class="col-12 col-lg-6">

                                <section class="astra-panel panel-blue">

                                    <header class="panel-header">
                                        <div>
                                            <i class="bi bi-journal-text"></i>
                                            LOGS DOS ÚLTIMOS ACESSOS
                                        </div>

                                        <div class="panel-actions">
                                            <i class="bi bi-arrows-fullscreen"></i>
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </div>
                                    </header>

                                    <div class="panel-body">

                                        <iframe
                                            src="components/logs.php"
                                            title="Logs dos últimos acessos"
                                            loading="lazy">
                                        </iframe>

                                    </div>

                                </section>

                            </div>


                            <!-- FICHA DE DADOS -->
                            <div class="col-12 col-lg-6">

                                <section class="astra-panel panel-cyan">

                                    <header class="panel-header">
                                        <div>
                                            <i class="bi bi-person-vcard-fill"></i>
                                            FICHA DE DADOS
                                        </div>

                                        <div class="panel-actions">
                                            <i class="bi bi-arrows-fullscreen"></i>
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </div>
                                    </header>

                                    <div class="panel-body">

                                        <iframe
                                            src="components/profile.php"
                                            title="Ficha de dados"
                                            loading="lazy">
                                        </iframe>

                                    </div>

                                </section>

                            </div>


                            <!-- MONITORAMENTO -->
                            <div class="col-12 col-lg-7">

                                <section class="astra-panel panel-blue">

                                    <header class="panel-header">

                                        <div>
                                            <i class="bi bi-bar-chart-fill"></i>
                                            MONITORAMENTO DE RECURSOS
                                        </div>

                                        <div class="panel-actions">
                                            <i class="bi bi-arrows-fullscreen"></i>
                                        </div>

                                    </header>

                                    <div class="panel-body">

                                        <iframe
                                            src="components/resources.php"
                                            title="Monitoramento de recursos"
                                            loading="lazy">
                                        </iframe>

                                    </div>

                                </section>

                            </div>


                            <!-- ALERTAS -->
                            <div class="col-12 col-lg-5">

                                <section class="astra-panel panel-red">

                                    <header class="panel-header">

                                        <div>
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                            ALERTAS DO SISTEMA
                                        </div>

                                        <div class="panel-actions">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </div>

                                    </header>

                                    <div class="panel-body">

                                        <iframe
                                            src="components/alerts.php"
                                            title="Alertas do sistema"
                                            loading="lazy">
                                        </iframe>

                                    </div>

                                </section>

                            </div>

                        </div>

                    </div>


                    <!-- =============================================
                     COLUNA DIREITA
                ============================================== -->
                    <div class="col-12 col-xl-3">

                        <!-- LOGO / USUÁRIO -->

                        <section class="user-panel">

                            <div class="logo">

                                <div class="logo-symbol">
                                    A
                                </div>

                                <div>
                                    <div class="logo-name">ASTRA</div>
                                    <div class="logo-subtitle">
                                        ASSISTENTE INTELIGENTE
                                    </div>
                                </div>

                            </div>

                            <hr>

                            <div class="user-info">

                                <div class="user-avatar">
                                    <i class="bi bi-person-fill"></i>
                                </div>

                                <div>
                                    <small>USUÁRIO ATUAL</small>

                                    <div class="user-name">
                                        <?php
                                        echo htmlspecialchars(
                                            $_SESSION['user_name'] ?? 'Usuário'
                                        );
                                        ?>
                                    </div>

                                    <div class="user-level">
                                        NÍVEL: ADMINISTRADOR
                                    </div>
                                </div>

                            </div>

                        </section>


                        <!-- COMUNICAÇÕES -->

                        <section class="astra-panel panel-purple mt-3">

                            <header class="panel-header">

                                <div>
                                    <i class="bi bi-headset"></i>
                                    COMUNICAÇÕES
                                </div>

                            </header>

                            <div class="panel-body">

                                <iframe
                                    src="components/communications.php"
                                    title="Comunicações"
                                    loading="lazy">
                                </iframe>

                            </div>

                        </section>


                        <!-- NAVEGAÇÃO -->

                        <section class="astra-panel panel-purple mt-3">

                            <header class="panel-header">

                                <div>
                                    <i class="bi bi-compass-fill"></i>
                                    NAVEGAÇÃO RÁPIDA
                                </div>

                            </header>

                            <div class="panel-body p-0">

                                <nav class="quick-nav">

                                    <a href="#">
                                        <span>
                                            <i class="bi bi-speedometer2"></i>
                                            Dashboard
                                        </span>

                                        <i class="bi bi-chevron-right"></i>
                                    </a>

                                    <a href="#">
                                        <span>
                                            <i class="bi bi-list-task"></i>
                                            Tarefas
                                        </span>

                                        <i class="bi bi-chevron-right"></i>
                                    </a>

                                    <a href="#">
                                        <span>
                                            <i class="bi bi-bar-chart"></i>
                                            Relatórios
                                        </span>

                                        <i class="bi bi-chevron-right"></i>
                                    </a>

                                    <a href="#">
                                        <span>
                                            <i class="bi bi-gear"></i>
                                            Configurações
                                        </span>

                                        <i class="bi bi-chevron-right"></i>
                                    </a>

                                    <a href="#">
                                        <span>
                                            <i class="bi bi-people"></i>
                                            Usuários
                                        </span>

                                        <i class="bi bi-chevron-right"></i>
                                    </a>

                                    <a href="#">
                                        <span>
                                            <i class="bi bi-terminal"></i>
                                            Logs
                                        </span>

                                        <i class="bi bi-chevron-right"></i>
                                    </a>

                                </nav>

                            </div>

                        </section>


                        <!-- MAPA / WIDGET -->

                        <section class="astra-panel panel-blue mt-3">

                            <header class="panel-header">

                                <div>
                                    <i class="bi bi-globe-americas"></i>
                                    REDE
                                </div>

                            </header>

                            <div class="panel-body">

                                <iframe
                                    src="components/network.php"
                                    title="Mapa da rede"
                                    loading="lazy">
                                </iframe>

                            </div>

                        </section>

                    </div>

                </div>

            </div>


            <!-- =====================================================
             FOOTER
        ====================================================== -->

            <footer class="astra-footer">

                <div class="footer-tech">
                    <span>PHP 8.2</span>
                    <span>MySQL 8</span>
                    <span>NGINX</span>
                </div>

                <div>
                    TEMPO DO SERVIDOR<br>
                    <strong id="server-clock">--:--:--</strong>
                </div>

                <div>
                    VERSÃO DO SISTEMA<br>
                    <strong>2.4.0</strong>
                </div>

                <div class="security-status">
                    <i class="bi bi-lock-fill"></i>
                    CRIPTOGRAFIA ATIVA
                </div>

            </footer>

        </main>

    </div>


    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
    </script>

    <script src="assets/js/assistente.js"></script>

</body>

</html>