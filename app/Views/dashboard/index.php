<?php $apps = is_array($apps ?? null) ? $apps : []; ?>

<section class="col-12 dashboard-apps" aria-labelledby="dashboard-title">
    <header class="dashboard-apps-header">
        <div>
            <span>APLICATIVOS HABILITADOS</span>
            <h1 id="dashboard-title">Dashboard</h1>
        </div>
        <strong><?= count($apps) ?> <?= count($apps) === 1 ? 'APP' : 'APPS' ?></strong>
    </header>

    <?php if ($apps === []): ?>
        <div class="dashboard-empty">
            <i class="bi bi-grid"></i>
            <h2>Nenhum aplicativo disponível</h2>
            <p>Não há aplicativos de nível 0 ou permissões individuais para este usuário.</p>
        </div>
    <?php else: ?>
        <div class="app-card-grid">
            <?php foreach ($apps as $app): ?>
                <?php
                $storedUrl = trim((string) $app['url']);
                $appUrl = preg_match('~^https?://~i', $storedUrl)
                    ? $storedUrl
                    : site_url(ltrim($storedUrl, '/'));
                ?>
                <a class="dashboard-app-card" href="<?= esc($appUrl, 'attr') ?>">
                    <div class="dashboard-app-icon">
                        <i class="bi <?= esc($app['icon'], 'attr') ?>"></i>
                    </div>
                    <div class="dashboard-app-info">
                        <h2><?= esc($app['name']) ?></h2>
                        <span><?= (int) $app['access_level'] === 0 ? 'Acesso padrão' : 'Acesso concedido' ?></span>
                    </div>
                    <i class="bi bi-arrow-up-right card-arrow" aria-hidden="true"></i>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<style>
.dashboard-apps{padding:10px 12px 28px}.dashboard-apps-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px}.dashboard-apps-header span{color:var(--cyan);font-size:10px;letter-spacing:2px}.dashboard-apps-header h1{margin:5px 0 0;color:#fff;font-size:27px}.dashboard-apps-header>strong{padding:7px 13px;border:1px solid #164c70;border-radius:18px;color:var(--cyan);background:#05243a;font-size:11px}.app-card-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:16px}.dashboard-app-card{position:relative;min-height:170px;padding:20px;border:1px solid #12334a;border-radius:14px;color:#b9d5e9;background:linear-gradient(145deg,#061624fa,#020a12fa);text-decoration:none;box-shadow:0 8px 25px #0003;transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}.dashboard-app-card:hover{transform:translateY(-4px);border-color:var(--cyan);box-shadow:0 12px 30px #00d9ff18;color:#fff}.dashboard-app-icon{width:58px;height:58px;display:grid;place-items:center;margin-bottom:20px;border:1px solid #164c70;border-radius:13px;color:var(--cyan);background:#05243a;font-size:27px}.dashboard-app-info h2{margin:0 0 6px;color:#fff;font-size:18px}.dashboard-app-info span{color:#7193ac;font-size:10px;text-transform:uppercase;letter-spacing:1px}.card-arrow{position:absolute;top:18px;right:18px;color:#57738e;font-size:16px}.dashboard-app-card:hover .card-arrow{color:var(--cyan)}.dashboard-empty{min-height:430px;display:grid;place-content:center;text-align:center;border:1px dashed #164c70;border-radius:14px;color:#57738e;background:#03101b80}.dashboard-empty>i{font-size:48px}.dashboard-empty h2{margin:12px 0 4px;color:#8db5d1;font-size:20px}.dashboard-empty p{margin:0;font-size:13px}@media(max-width:600px){.dashboard-apps-header{align-items:flex-start;gap:12px;flex-direction:column}.app-card-grid{grid-template-columns:1fr}}
</style>
