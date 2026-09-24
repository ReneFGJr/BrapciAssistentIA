<section class="person-page col-12 p-3 text-light">
    <h1 class="h3 mb-4">Cadastrar pessoa</h1>
    <?= view('person/messages') ?>
    <?php if ($error !== null): ?><div class="alert alert-danger" role="alert"><?= esc($error) ?></div><?php endif; ?>
    <form action="<?= site_url('person/new') ?>" method="get" class="card bg-dark border-secondary mb-4">
        <div class="card-body">
            <label for="lookup-query" class="form-label">Qual é o nome ou e-mail da pessoa?</label>
            <div class="d-flex flex-wrap gap-2">
                <input id="lookup-query" name="q" type="search" required maxlength="254" autofocus
                    class="form-control w-auto flex-grow-1" value="<?= esc($query, 'attr') ?>" placeholder="Nome ou e-mail">
                <button type="submit" class="btn btn-info">Buscar cadastro</button>
                <a href="<?= site_url('person') ?>" class="btn btn-outline-light">Cancelar</a>
            </div>
        </div>
    </form>
    <?php if ($result !== null): ?>
        <section class="card bg-dark border-secondary" aria-labelledby="lookup-results-title">
            <div class="card-body">
                <h2 id="lookup-results-title" class="h5">Selecione o cadastro</h2>
                <p><?= (int) $result['total'] ?> resultados encontrados. Confira o nome, e-mail e instituição.</p>
                <div class="table-responsive">
                    <table class="table table-dark align-middle">
                        <thead><tr><th scope="col">Nome</th><th scope="col">E-mail</th><th scope="col">Instituição</th><th scope="col">Ação</th></tr></thead>
                        <tbody>
                        <?php foreach ($result['users'] as $user): ?>
                            <?php $data = \App\Libraries\BrapciUserLookup::prefill($user); ?>
                            <tr>
                                <td><?= esc($data['full_name']) ?></td>
                                <td><?= esc($data['email_1']) ?></td>
                                <td><?= esc($data['institution']) ?></td>
                                <td><a class="btn btn-sm btn-outline-info" href="<?= esc(site_url('person/new') . '?' . http_build_query(['q' => $query, 'source' => $user['id_us']]), 'attr') ?>">Selecionar</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($result['pages'] > 1): ?>
                    <nav class="d-flex gap-3 align-items-center" aria-label="Resultados da busca">
                        <?php if ($result['page'] > 1): ?><a class="btn btn-outline-light" href="<?= esc(site_url('person/new') . '?' . http_build_query(['q' => $query, 'page' => $result['page'] - 1]), 'attr') ?>">Anterior</a><?php endif; ?>
                        <span>Página <?= (int) $result['page'] ?> de <?= (int) $result['pages'] ?></span>
                        <?php if ($result['page'] < $result['pages']): ?><a class="btn btn-outline-light" href="<?= esc(site_url('person/new') . '?' . http_build_query(['q' => $query, 'page' => $result['page'] + 1]), 'attr') ?>">Próxima</a><?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</section>