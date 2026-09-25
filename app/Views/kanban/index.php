<section class="kanban-page col-12 p-3 text-light">
    <?= view('person/messages', ['inFooter' => true]) ?>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Kanban</h1>
        <a class="btn btn-info" href="<?= site_url('kanban/new') ?>" title="Novo cartão" aria-label="Novo cartão"><i class="bi bi-plus-lg" aria-hidden="true"></i></a>
    </div>
    <div class="kanban-board">
        <?php foreach (\App\Models\KanbanModel::STATUSES as $status => $label): ?>
            <?php if ($status === 'close') { continue; } ?>
            <section class="kanban-column" aria-labelledby="kanban-<?= $status ?>">
                <h2 id="kanban-<?= $status ?>" class="h5 d-flex justify-content-between"><?= esc($label) ?><span class="badge bg-secondary"><?= count($columns[$status]) ?></span></h2>
                <?php if ($columns[$status] === []): ?><p class="kanban-empty">Nenhum cartão.</p><?php endif; ?>
                <?php foreach ($columns[$status] as $item): ?>
                    <article class="kanban-note kanban-priority-<?= esc($item['priority'], 'attr') ?>">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <h3 class="h6 mb-1"><button type="button" class="kanban-open stretched-link" data-bs-toggle="modal" data-bs-target="#kanban-detail-<?= (int) $item['id'] ?>"><?= esc($item['title']) ?></button></h3>
                            <a class="kanban-edit" href="<?= site_url('kanban/' . $item['id'] . '/edit') ?>" title="Editar cartão" aria-label="<?= esc('Editar ' . $item['title'], 'attr') ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                        </div>
                        <span class="kanban-priority"><?= esc(\App\Models\KanbanModel::PRIORITIES[$item['priority']]) ?></span>

                    </article>
                    <div class="modal fade kanban-detail" id="kanban-detail-<?= (int) $item['id'] ?>" tabindex="-1" aria-labelledby="kanban-detail-title-<?= (int) $item['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h2 class="modal-title h5" id="kanban-detail-title-<?= (int) $item['id'] ?>"><?= esc($item['title']) ?></h2>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                </div>
                                <div class="modal-body">
                                    <dl class="row">
                                        <div class="col-sm-6"><dt>Status</dt><dd><?= esc($label) ?></dd></div>
                                        <div class="col-sm-6"><dt>Prioridade</dt><dd><?= esc(\App\Models\KanbanModel::PRIORITIES[$item['priority']]) ?></dd></div>
                                    </dl>
                                    <h3 class="h6">Descrição</h3>
                                    <div class="kanban-detail-text"><?= $item['description'] !== '' ? \App\Libraries\KanbanText::render($item['description']) : 'Sem descrição.' ?></div>
                                </div>
                                <div class="modal-footer">
                                    <a class="btn btn-outline-info" href="<?= site_url('kanban/' . $item['id'] . '/edit') ?>" title="Editar cartão" aria-label="Editar cartão"><i class="bi bi-pencil-square" aria-hidden="true"></i></a>
                                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Fechar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </div>
</section>