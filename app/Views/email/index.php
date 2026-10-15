<section class="col-12 p-3 text-light">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3">Gerenciamento de e-mail</h1>
        <a class="btn btn-primary" href="<?= site_url('email/edit/0') ?>" title="Novo e-mail" aria-label="Novo e-mail"><i class="bi bi-plus-lg" aria-hidden="true"></i></a>
    </div>
    <p>Contas de e-mail gerenciadas para seu usuário.</p>
    <div class="table-responsive">
        <table class="table table-dark table-striped align-middle">
            <thead><tr><th>E-mail</th><th>Servidor</th><th>Porta</th><th>Protocolo</th><th>Segurança</th><th>Status</th><th>Criado em (UTC)</th><th>Atualizado em (UTC)</th><th class="text-end">Ações</th></tr></thead>
            <tbody>
            <?php foreach ($accounts as $account): ?>
                <tr>
                    <td><?= esc($account['email_user']) ?> <?php if (!empty($account['is_preferred'])): ?><span class="badge bg-warning text-dark"><i class="bi bi-star-fill" aria-hidden="true"></i> Preferencial</span><?php endif; ?></td>
                    <?php foreach (['mail_user', 'mail_port_user', 'protocol'] as $field): ?><td><?= esc($account[$field]) ?></td><?php endforeach; ?>
                    <td><?= $account['security'] === 'none' ? 'Sem criptografia' : esc(strtoupper($account['security'])) ?></td>
                    <td><span class="badge <?= (int) $account['status'] === 1 ? 'bg-success' : 'bg-secondary' ?>"><?= (int) $account['status'] === 1 ? 'Ativo' : 'Inativo' ?></span></td>
                    <td><?= esc($account['created']) ?></td><td><?= esc($account['lastupdated']) ?></td>
                    <td class="text-nowrap text-end">
                        <div class="d-flex justify-content-end align-items-center gap-2">
                        <?php if (empty($account['is_preferred'])): ?><form class="m-0" method="post" action="<?= site_url('email/prefer/' . $account['id']) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-warning" title="Definir como conta preferencial" aria-label="Definir como conta preferencial"><i class="bi bi-star" aria-hidden="true"></i></button></form><?php endif; ?>
                        <a class="btn btn-sm btn-primary" href="<?= site_url('email/account/' . $account['id']) ?>" title="Caixa de entrada" aria-label="Caixa de entrada"><i class="bi bi-inbox" aria-hidden="true"></i></a>
                        <a class="btn btn-sm btn-outline-light" href="<?= site_url('email/edit/' . $account['id']) ?>" title="Editar" aria-label="Editar"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                        <form class="m-0" method="post" action="<?= site_url('email/delete/' . $account['id']) ?>" onsubmit="return confirm('Excluir esta conta de e-mail?');">
                            <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger" title="Excluir" aria-label="Excluir"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($accounts === []): ?><tr><td colspan="9">Nenhuma conta de e-mail cadastrada.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="text-end"><a class="btn btn-outline-light" href="<?= site_url('tools') ?>" title="Voltar às ferramentas" aria-label="Voltar às ferramentas"><i class="bi bi-arrow-left" aria-hidden="true"></i></a></div>
</section>
