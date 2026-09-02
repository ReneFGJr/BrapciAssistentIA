<?php
$error = session()->getFlashdata('error') ?? ($error ?? null);
?>

<div class="col-12 signin-screen" id="signin-screen">
    <section class="signin-card" aria-labelledby="signin-title">
        <img class="signin-logo" src="<?= base_url('assets/img/logo/logo.png') ?>" alt="Logo do Assistente IA">

        <div class="signin-panel">
            <h1 id="signin-title">Acesso ao sistema</h1>

            <div class="signin-error" role="alert" aria-live="polite"><?php if ($error): ?>
                <?= esc($error) ?>
            <?php endif; ?></div>

            <form action="<?= site_url('signin') ?>" method="post">
                <?= csrf_field() ?>
                <label for="login">Login</label>
                <input id="login" name="login" type="text" autocomplete="username" placeholder="Digite seu login" required>
                <label for="password">Senha</label>
                <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Digite sua senha" required>
                <button type="submit">Entrar</button>
            </form>
        </div>
    </section>
</div>

<style>
.signin-screen{min-height:calc(100vh - 190px);display:grid;place-items:center;padding:24px}
.signin-card{width:min(100%,460px);display:flex;flex-direction:column;align-items:center;gap:22px}
.signin-logo{width:70vw;height:70vh;object-fit:contain;cursor:pointer;transition:width .35s ease,height .35s ease,filter .35s ease}
.signin-panel{width:100%;max-height:0;overflow:hidden;opacity:0;transform:translateY(16px);pointer-events:none;transition:max-height .45s ease,opacity .3s ease,transform .3s ease}
.signin-card:hover .signin-panel,.signin-card:focus-within .signin-panel,.signin-screen.is-active .signin-panel{max-height:520px;opacity:1;transform:translateY(0);pointer-events:auto}
.signin-card:hover .signin-logo,.signin-card:focus-within .signin-logo,.signin-screen.is-active .signin-logo{width:150px;height:150px;filter:drop-shadow(0 0 14px #00d9ff66)}
.signin-panel h1{margin:0 0 14px;color:#fff;font-size:22px;text-align:center}
.signin-error{min-height:42px;margin-bottom:12px;padding:10px 12px;border:1px solid #ff554566;border-radius:8px;color:#ff8b80;background:#ff55450d}
.signin-error:empty{visibility:hidden}
.signin-panel form{display:grid;gap:10px}
.signin-panel label{color:#8db5d1;font-size:13px}
.signin-panel input{width:100%;min-height:56px;padding:12px 16px;border:1px solid #164c70;border-radius:10px;outline:none;color:#fff;background:#03101b;font-size:18px}
.signin-panel input:focus{border-color:#00d9ff;box-shadow:0 0 0 3px #00d9ff26}
.signin-panel button{min-height:52px;margin-top:8px;border:1px solid #00d9ff;border-radius:10px;color:#02101a;background:#00d9ff;font-size:16px;font-weight:700;text-transform:uppercase}
@media(max-width:768px){.signin-screen{min-height:calc(100vh - 230px);padding:16px}}
</style>

<script>
(() => {
    const screen = document.getElementById('signin-screen');
    const login = document.getElementById('login');
    if (!screen || !login) return;
    document.addEventListener('keydown', (event) => {
        if (event.ctrlKey || event.altKey || event.metaKey) return;
        screen.classList.add('is-active');
        if (!['Tab', 'Shift', 'Escape', 'Enter'].includes(event.key)) login.focus();
    }, { once: true });
})();
</script>
