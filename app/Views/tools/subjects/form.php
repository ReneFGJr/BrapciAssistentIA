<section class="col-12 p-3 text-light">
    <h1 class="h3 mb-4"><?= $subject === null ? 'Novo assunto' : 'Editar assunto' ?></h1>
    <form method="post" action="<?= site_url('tools/subjects' . ($subject === null ? '' : '/' . $subject['id'] . '/update')) ?>">
        <?= csrf_field() ?>
        <div class="mb-3">
            <label for="subject-name" class="form-label">Nome do assunto</label>
            <input id="subject-name" class="form-control" name="name" maxlength="150" required value="<?= esc(old('name', $subject['name'] ?? ''), 'attr') ?>">
        </div>
        <button class="btn btn-primary">Salvar</button>
        <a class="btn btn-outline-light" href="<?= site_url('tools/subjects') ?>">Cancelar</a>
    </form>
</section>
