<?php
$footerUser = session()->get('auth_user');
$footerGivenName = session()->get('logged_in') && is_array($footerUser)
    ? ($footerUser['givenName'] ?? null)
    : null;
?>

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
        MENSAGEM<br>
        <strong id="footer-message" role="status" aria-live="polite" data-footer-message="<?= esc((string) (session()->getFlashdata('error') ?: session()->getFlashdata('success') ?: ''), 'attr') ?>" data-footer-status="<?= session()->getFlashdata('error') ? 'error' : 'success' ?>"><?= esc((string) (session()->getFlashdata('error') ?: session()->getFlashdata('success') ?: 'none')) ?></strong>
    </div>

    <?php if ($footerGivenName): ?>
        <div>
            USUÁRIO<br>
            <strong>
                <a class="footer-profile-link" href="<?= site_url('profile') ?>">
                    <?= esc($footerGivenName) ?>
                </a>
            </strong>
        </div>
    <?php endif; ?>

    <div>
        VERSÃO DO SISTEMA<br>
        <strong>2.4.0</strong>
    </div>

    <div class="security-status">
        <i class="bi bi-lock-fill"></i>
        CRIPTOGRAFIA ATIVA
    </div>

</footer>
