<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Não foi possível abrir a caixa de entrada</title></head>
<body style="background:#172131;color:#ecf1fa;font-family:Arial,sans-serif;padding:32px;line-height:1.6">
    <main role="alert">
        <h1>Não foi possível abrir a caixa de entrada</h1>
        <p>O servidor encontrou um erro ao carregar os e-mails salvos. Nenhuma mensagem foi excluída.</p>
        <p>Consulte o registro em <code>writable/logs</code> usando o código <strong><?= esc($reference) ?></strong>. Verifique também se as migrações do banco foram aplicadas no servidor com <code>php spark migrate</code>.</p>
        <a style="color:#66d9ff" href="<?= site_url('email/account') ?>">Tentar novamente</a>
    </main>
</body>
</html>
