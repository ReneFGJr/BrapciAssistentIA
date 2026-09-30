<section class="card bg-dark text-light border-secondary p-3 mt-4" aria-labelledby="categories-title">
    <h2 id="categories-title" class="h5">Categorias</h2>
    <?php if ($categories === []): ?><p>Nenhuma categoria selecionada.</p><?php endif; ?>
    <ul class="list-unstyled">
    <?php foreach ($categories as $category): ?>
        <li class="d-flex justify-content-between align-items-center gap-2 mb-2">
            <span><?= esc($category['name']) ?></span>
            <form method="post" action="<?= site_url('notes/' . $note['id'] . '/categories/' . $category['id'] . '/remove') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="<?= esc('Remover categoria ' . $category['name'], 'attr') ?>">Remover</button>
            </form>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php if ($availableCategories !== []): ?>
        <form method="post" action="<?= site_url('notes/' . $note['id'] . '/categories') ?>">
            <?= csrf_field() ?>
            <label for="note-category" class="form-label">Selecionar categoria existente</label>
            <div class="d-flex flex-wrap gap-2">
                <select id="note-category" name="subject_id" class="form-select" required>
                    <option value="">Selecione uma categoria</option>
                    <?php foreach ($availableCategories as $category): ?>
                        <option value="<?= (int) $category['id'] ?>"><?= esc($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-info" type="submit">Adicionar categoria</button>
            </div>
        </form>
    <?php endif; ?>
    <form method="post" action="<?= site_url('notes/' . $note['id'] . '/categories/new') ?>" class="mt-3">
        <?= csrf_field() ?>
        <label for="category-name" class="form-label">Criar categoria para meu usuário</label>
        <input id="category-name" name="category_name" class="form-control mb-2" maxlength="150" required value="<?= esc(old('category_name', '', false), 'attr') ?>" placeholder="Nome da categoria">
        <button class="btn btn-info" type="submit">Criar e adicionar</button>
    </form>
</section>
