<section class="kanban-page col-12 p-3 text-light">
    <?= view('person/messages', ['inFooter' => true]) ?>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Kanban</h1>
        <a class="btn btn-info" href="<?= site_url('kanban/new') ?>" title="Novo cartão" aria-label="Novo cartão"><i class="bi bi-plus-lg" aria-hidden="true"></i></a>
    </div>
    <div class="kanban-board">
        <?php foreach (\App\Models\KanbanModel::STATUSES as $status => $label): ?>
            <section class="kanban-column" aria-labelledby="kanban-<?= $status ?>">
                <h2 id="kanban-<?= $status ?>" class="h5 d-flex justify-content-between"><?= esc($label) ?><span class="badge bg-secondary"><?= count($columns[$status]) ?></span></h2>
                <?php if ($columns[$status] === []): ?><p class="kanban-empty">Nenhum cartão.</p><?php endif; ?>
                <?php foreach ($columns[$status] as $item): ?>
                    <article class="kanban-note kanban-priority-<?= esc($item['priority'], 'attr') ?>">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <h3 class="h6"><?= esc($item['title']) ?></h3>
                            <a class="kanban-edit" href="<?= site_url('kanban/' . $item['id'] . '/edit') ?>" title="Editar cartão" aria-label="<?= esc('Editar ' . $item['title'], 'attr') ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                        </div>
                        <span class="kanban-priority"><?= esc(\App\Models\KanbanModel::PRIORITIES[$item['priority']]) ?></span>
                        <?php if ($item['description'] !== ''): ?><p class="kanban-description"><?= esc($item['description']) ?></p><?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </div>
</section>