<section class="col-12 p-3 text-light">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">Gerenciamento de e-mail</h1>
        <a class="btn btn-primary" href="<?= site_url('email/edit/0') ?>">Novo e-mail</a>
    </div>
    <p>Contas de e-mail gerenciadas para seu usuário.</p>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle">
            <thead><tr><th>E-mail</th><th>Servidor</th><th>Porta</th><th>Protocolo</th><th>Segurança</th><th>Status</th><th>Criado em (UTC)</th><th>Atualizado em (UTC)</th><th>Ações</th></tr></thead>
            <tbody>
            <?php foreach ($accounts as $account): ?>
                <tr>
                    <?php foreach (['email_user', 'mail_user', 'mail_port_user', 'protocol'] as $field): ?><td><?= esc($account[$field]) ?></td><?php endforeach; ?>
                    <td><?= $account['security'] === 'none' ? 'Sem criptografia' : esc(strtoupper($account['security'])) ?></td>
                    <td><span class="badge <?= (int) $account['status'] === 1 ? 'bg-success' : 'bg-secondary' ?>"><?= (int) $account['status'] === 1 ? 'Ativo' : 'Inativo' ?></span></td>
                    <td><?= esc($account['created']) ?></td><td><?= esc($account['lastupdated']) ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-primary" href="<?= site_url('email/account/' . $account['id']) ?>">Caixa de entrada</a>
                        <a class="btn btn-sm btn-outline-light" href="<?= site_url('email/edit/' . $account['id']) ?>">Editar</a>
                        <form class="d-inline" method="post" action="<?= site_url('email/delete/' . $account['id']) ?>" onsubmit="return confirm('Excluir esta conta de e-mail?');">
                            <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($accounts === []): ?><tr><td colspan="9">Nenhuma conta de e-mail cadastrada.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <a class="btn btn-outline-light" href="<?= site_url('tools') ?>">Voltar às ferramentas</a>
</section>
