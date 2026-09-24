<section class="card bg-dark border-secondary mt-4" aria-labelledby="share-title">
    <div class="card-body">
        <h2 id="share-title" class="h5">Compartilhar cadastro</h2>
        <p>O dono pode editar e gerenciar os acessos. O destinatário deve entrar com o e-mail informado.</p>
        <div class="person-share-options">
            <?php $whatsappUrl = 'https://wa.me/?text=' . rawurlencode('Cadastro de ' . $person['nickname'] . ': ' . site_url('person/' . $person['id'])); ?>
            <a href="<?= esc($whatsappUrl, 'attr') ?>" class="btn person-whatsapp" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-whatsapp" aria-hidden="true"></i> Enviar por WhatsApp
            </a>
            <p>Envie o link do cadastro. Para abrir, o destinatário precisa entrar na conta e ter acesso concedido pelo e-mail abaixo.</p>
        </div>
        <form action="<?= site_url('person/' . $person['id'] . '/share') ?>" method="post" class="row g-3 mb-4">
            <?= csrf_field() ?>
            <div class="col-md-4">
                <label for="share-email" class="form-label">E-mail do usuário</label>
                <input id="share-email" type="email" name="email" maxlength="254" required class="form-control" value="<?= old('email', '', 'attr') ?>">
            </div>
            <div class="col-md-3">
                <label for="share-level" class="form-label">Nível de acesso</label>
                <select id="share-level" name="access_level" class="form-select">
                    <option value="read">Leitura</option>
                    <option value="edit" <?= old('access_level') === 'edit' ? 'selected' : '' ?>>Edição</option>
                </select>
            </div>
            <div class="col-md-5">
                <label for="share-expiration" class="form-label">Expiração (<?= esc(config('App')->appTimezone) ?>)</label>
                <input id="share-expiration" type="datetime-local" name="expires_at" class="form-control" value="<?= old('expires_at', '', 'attr') ?>">
                <small>Deixe em branco para acesso sem expiração.</small>
            </div>
            <div class="col-12"><button class="btn btn-info" type="submit">Compartilhar / atualizar acesso</button></div>
        </form>
        <div class="table-responsive">
            <table class="table table-dark align-middle">
                <thead><tr><th>E-mail</th><th>Nível</th><th>Expiração</th><th>Situação</th><th>Ações</th></tr></thead>
                <tbody>
                <?php if ($shares === []): ?><tr><td colspan="5">Nenhum acesso compartilhado.</td></tr><?php endif; ?>
                <?php foreach ($shares as $share): ?>
                    <?php
                    $expires = $share['expires_at'] === null ? null : new DateTimeImmutable($share['expires_at'], new DateTimeZone('UTC'));
                    $expired = $expires !== null && $expires->getTimestamp() <= time();
                    ?>
                    <tr>
                        <td><?= esc($share['email']) ?></td>
                        <td><?= $share['access_level'] === 'edit' ? 'Edição' : 'Leitura' ?></td>
                        <td><?= $expires === null ? 'Sem expiração' : esc($expires->setTimezone(new DateTimeZone(config('App')->appTimezone))->format('d/m/Y H:i')) ?></td>
                        <td><?= $expired ? 'Expirado' : ($share['user'] === null ? 'Aguardando acesso' : 'Ativo') ?></td>
                        <td>
                            <form action="<?= site_url('person/' . $person['id'] . '/shares/' . $share['id'] . '/revoke') ?>" method="post">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-outline-danger">Revogar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>