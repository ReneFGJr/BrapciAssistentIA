<!-- HEADER -->
<header class="astra-header">

    <div class="system-title">
        <?php if (session('logged_in') === true): ?>
        <button class="btn btn-outline-info sidebar-toggle" type="button" aria-controls="main-navigation" aria-expanded="false"><i class="bi bi-list" aria-hidden="true"></i> Menu</button>
        <?php endif; ?>
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
