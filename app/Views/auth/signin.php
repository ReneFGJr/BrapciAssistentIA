<?php
$error = session()->getFlashdata('error') ?? ($error ?? null);
$success = session()->getFlashdata('success');
$errorDetail = session()->getFlashdata('error_detail');
$user = session()->get('auth_user');
$isAuthenticated = session()->get('logged_in') && is_array($user);
$footerMessage = $error ?: $success;
$footerStatus = $error ? 'error' : ($success ? 'success' : '');
?>

<div class="col-12 signin-screen<?= ($isAuthenticated || $error) ? ' is-active' : '' ?>" id="signin-screen"
    data-footer-message="<?= esc((string) ($footerMessage ?? ''), 'attr') ?>"
    data-footer-status="<?= esc($footerStatus, 'attr') ?>">
    <section class="signin-card" aria-labelledby="signin-title">
        <img class="signin-logo" src="<?= base_url('assets/img/logo/logo.png') ?>" alt="Logo do Assistente IA">

        <div class="signin-panel">
            <?php if ($isAuthenticated): ?>
                <div class="signin-authenticated">
                    <h1 id="signin-title">Bem-vindo, <?= esc($user['displayName'] ?: $user['givenName'] ?: $user['email'] ?: 'usuário') ?></h1>
                    <?php if (! empty($user['email'])): ?>
                        <p><?= esc($user['email']) ?></p>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="signin-success" role="status"><?= esc($success) ?></div>
                    <?php endif; ?>
                    <form action="<?= site_url('logout') ?>" method="post">
                        <?= csrf_field() ?>
                        <button type="submit">Sair</button>
                    </form>
                </div>
            <?php else: ?>
                <h1 id="signin-title">Acesso ao sistema</h1>
                <form action="<?= site_url('signin') ?>" method="post">
                    <?= csrf_field() ?>
                    <label for="login">Login</label>
                    <input id="login" name="login" type="text" value="<?= esc(old('login')) ?>"
                        autocomplete="username" placeholder="Digite seu login" required>
                    <label for="password">Senha</label>
                    <input id="password" name="password" type="password" autocomplete="current-password"
                        placeholder="Digite sua senha" required>
                    <button type="submit">Entrar</button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php if ($error && is_array($errorDetail)): ?>
<div class="modal fade" id="auth-error-modal" tabindex="-1" aria-labelledby="auth-error-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark text-light border-danger">
            <div class="modal-header border-danger">
                <h2 class="modal-title fs-5" id="auth-error-modal-title">Retorno do serviço de autenticação</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <pre class="m-0 p-3 rounded text-start text-light bg-black overflow-auto" style="max-height:55vh"><code><?= esc(json_encode($errorDetail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></code></pre>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.signin-screen{min-height:calc(100vh - 190px);display:grid;place-items:center;padding:24px}
.signin-card{width:min(100%,460px);display:flex;flex-direction:column;align-items:center;gap:22px}
.signin-logo{width:70vw;height:70vh;object-fit:contain;cursor:pointer;transition:width .35s ease,height .35s ease,filter .35s ease}
.signin-panel{width:100%;max-height:0;overflow:hidden;opacity:0;transform:translateY(16px);pointer-events:none;transition:max-height .45s ease,opacity .3s ease,transform .3s ease}
.signin-card:focus-within .signin-panel,.signin-screen.is-active .signin-panel{max-height:520px;opacity:1;transform:translateY(0);pointer-events:auto}
.signin-card:focus-within .signin-logo,.signin-screen.is-active .signin-logo{width:150px;height:150px;filter:drop-shadow(0 0 14px #00d9ff66)}
.signin-panel h1{margin:0 0 14px;color:#fff;font-size:22px;text-align:center}
.signin-success{min-height:42px;margin-bottom:12px;padding:10px 12px;border:1px solid #23e76966;border-radius:8px;color:#6df29a;background:#23e7690d;text-align:center}
.signin-panel form{display:grid;gap:10px}
.signin-panel label{color:#8db5d1;font-size:13px}
.signin-panel input{width:100%;min-height:56px;padding:12px 16px;border:1px solid #164c70;border-radius:10px;outline:none;color:#fff;background:#03101b;font-size:18px}
.signin-panel input:focus{border-color:#00d9ff;box-shadow:0 0 0 3px #00d9ff26}
.signin-panel button{width:100%;min-height:52px;margin-top:8px;border:1px solid #00d9ff;border-radius:10px;color:#02101a;background:#00d9ff;font-size:16px;font-weight:700;text-transform:uppercase}
.signin-authenticated{text-align:center}.signin-authenticated p{color:#8db5d1}
@media(max-width:768px){.signin-screen{min-height:calc(100vh - 230px);padding:16px}}
</style>
