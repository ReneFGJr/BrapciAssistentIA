<?php
$user = is_array($user ?? null) ? $user : [];
$displayName = $user['displayName'] ?? $user['user'] ?? $user['givenName'] ?? 'Usuário';
?>

<div class="col-12 profile-screen">
    <section class="profile-card" aria-labelledby="profile-title">
        <header class="profile-heading">
            <div class="profile-avatar" aria-hidden="true">
                <?= esc(mb_strtoupper(mb_substr((string) ($user['givenName'] ?? $displayName), 0, 1))) ?>
            </div>
            <div>
                <span class="profile-label">PERFIL DO USUÁRIO</span>
                <h1 id="profile-title"><?= esc($displayName) ?></h1>
            </div>
        </header>

        <div class="profile-grid">
            <div class="profile-field">
                <span>Nome</span>
                <strong><?= esc($user['givenName'] ?? 'Não informado') ?></strong>
            </div>

            <div class="profile-field">
                <span>E-mail</span>
                <strong><?= esc($user['email'] ?? 'Não informado') ?></strong>
            </div>

            <div class="profile-field">
                <span>ID do usuário</span>
                <strong><?= esc($user['id'] ?? 'Não informado') ?></strong>
            </div>

            <div class="profile-field">
                <span>Perfil administrativo</span>
                <strong class="<?= ! empty($user['admin']) ? 'profile-admin' : '' ?>">
                    <?= ! empty($user['admin']) ? 'Sim' : 'Não' ?>
                </strong>
            </div>
        </div>

        <div class="profile-actions">
            <a class="profile-back" href="<?= site_url('/') ?>">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>

            <form action="<?= site_url('logout') ?>" method="post">
                <?= csrf_field() ?>
                <button type="submit">
                    <i class="bi bi-box-arrow-right"></i> Sair
                </button>
            </form>
        </div>
    </section>
</div>

<style>
.profile-screen{min-height:calc(100vh - 190px);display:grid;place-items:center;padding:24px}
.profile-card{width:min(100%,760px);padding:28px;border:1px solid #164c70;border-radius:16px;background:linear-gradient(145deg,#061624fa,#020a12fa);box-shadow:0 0 24px #00d9ff1a}
.profile-heading{display:flex;align-items:center;gap:18px;padding-bottom:22px;border-bottom:1px solid #12334a}
.profile-avatar{width:72px;height:72px;display:grid;place-items:center;flex-shrink:0;border:2px solid var(--cyan);border-radius:50%;color:var(--cyan);background:#05243a;font-size:30px;font-weight:700}
.profile-label{color:var(--cyan);font-size:10px;letter-spacing:2px}
.profile-heading h1{margin:5px 0 0;color:#fff;font-size:25px}
.profile-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;margin-top:22px}
.profile-field{min-height:82px;padding:14px;border:1px solid #12334a;border-radius:10px;background:#03101b}
.profile-field span{display:block;margin-bottom:7px;color:#7193ac;font-size:10px;text-transform:uppercase;letter-spacing:1px}
.profile-field strong{color:#d9edf9;font-size:15px;overflow-wrap:anywhere}
.profile-field .profile-admin{color:var(--green)}
.profile-actions{display:flex;align-items:center;justify-content:space-between;margin-top:24px}
.profile-actions form{margin:0}
.profile-back,.profile-actions button{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:8px 18px;border:1px solid #164c70;border-radius:9px;color:var(--cyan);background:#05243a;text-decoration:none;font-size:13px}
.profile-actions button{border-color:#ff554566;color:#ff8b80;background:#ff55450d}
.profile-back:hover{border-color:var(--cyan);color:#fff}
.profile-actions button:hover{border-color:#ff5545;color:#fff}
@media(max-width:600px){.profile-screen{padding:12px}.profile-card{padding:20px}.profile-grid{grid-template-columns:1fr}.profile-actions{gap:12px;flex-wrap:wrap}}
</style>
