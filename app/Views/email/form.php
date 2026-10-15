<?php
$values = (array) session()->getFlashdata('email_values');
$values += $account ?? [];
$values += ['email_user' => '', 'mail_user' => '', 'mail_port_user' => '993', 'protocol' => 'imap', 'security' => 'ssl', 'status' => '1'];
$values += ['smtp_host' => '', 'smtp_port' => '587', 'smtp_security' => 'tls'];
?>
<section class="col-12 p-3 text-light">
    <h1 class="h3 mb-4"><?= $account === null ? 'Novo e-mail' : 'Editar e-mail' ?></h1>
    <form method="post" action="<?= site_url('email/edit/' . ($account['id'] ?? 0)) ?>">
        <?= csrf_field() ?>
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="email-user" class="form-label">E-mail</label>
                <input id="email-user" name="email_user" type="email" class="form-control" maxlength="254" required value="<?= esc($values['email_user'], 'attr') ?>">
            </div>
            <div class="col-md-6">
                <label for="pass-user" class="form-label">Senha</label>
                <input id="pass-user" name="pass_user" type="password" class="form-control" maxlength="4096" autocomplete="new-password" <?= $account === null ? 'required' : '' ?> aria-describedby="password-help">
                <div id="password-help" class="form-text text-light"><?= $account === null ? 'Informe a senha ou senha de aplicativo da conta.' : 'Deixe em branco para manter a senha atual.' ?></div>
            </div>
            <div class="col-md-8">
                <label for="mail-user" class="form-label">Servidor de e-mail</label>
                <input id="mail-user" name="mail_user" class="form-control" maxlength="253" placeholder="imap.exemplo.com" required value="<?= esc($values['mail_user'], 'attr') ?>">
            </div>
            <div class="col-md-4">
                <label for="mail-port-user" class="form-label">Porta</label>
                <input id="mail-port-user" name="mail_port_user" type="number" class="form-control" min="1" max="65535" required value="<?= esc($values['mail_port_user'], 'attr') ?>">
            </div>
            <?php foreach (['protocol' => ['Protocolo', ['imap' => 'IMAP', 'pop3' => 'POP3']], 'security' => ['Segurança', ['ssl' => 'SSL', 'tls' => 'TLS / STARTTLS', 'none' => 'Sem criptografia']], 'status' => ['Status', ['1' => 'Ativo', '0' => 'Inativo']]] as $field => [$label, $options]): ?>
                <div class="col-md-4">
                    <label for="<?= esc($field, 'attr') ?>" class="form-label"><?= esc($label) ?></label>
                    <select id="<?= esc($field, 'attr') ?>" name="<?= esc($field, 'attr') ?>" class="form-select" required>
                        <?php foreach ($options as $value => $text): ?><option value="<?= esc($value, 'attr') ?>" <?= (string) $values[$field] === (string) $value ? 'selected' : '' ?>><?= esc($text) ?></option><?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
            <div class="col-12 mt-4"><h2 class="h5">Envio de e-mail (SMTP)</h2><p class="small">Usa o mesmo e-mail e a mesma senha desta conta. Se o servidor SMTP ficar em branco, será usado o servidor de recebimento, com a porta e a segurança abaixo.</p></div>
            <div class="col-md-6"><label for="smtp-host" class="form-label">Servidor SMTP</label><input id="smtp-host" name="smtp_host" class="form-control" maxlength="253" placeholder="Mesmo servidor de recebimento" value="<?= esc($values['smtp_host'], 'attr') ?>"></div>
            <div class="col-md-3"><label for="smtp-port" class="form-label">Porta SMTP</label><input id="smtp-port" name="smtp_port" class="form-control" type="number" min="1" max="65535" required value="<?= esc($values['smtp_port'], 'attr') ?>"></div>
            <div class="col-md-3"><label for="smtp-security" class="form-label">Segurança SMTP</label><select id="smtp-security" name="smtp_security" class="form-select"><?php foreach (['tls' => 'TLS / STARTTLS', 'ssl' => 'SSL', 'none' => 'Sem criptografia'] as $value => $label): ?><option value="<?= esc($value, 'attr') ?>" <?= $values['smtp_security'] === $value ? 'selected' : '' ?>><?= esc($label) ?></option><?php endforeach; ?></select></div>
        </div>
        <button class="btn btn-primary">Salvar</button>
        <a class="btn btn-outline-light" href="<?= site_url('email/config') ?>">Cancelar</a>
    </form>
</section>
