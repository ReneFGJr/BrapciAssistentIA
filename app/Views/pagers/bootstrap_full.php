<?php $pager->setSurroundCount(2); ?>
<nav aria-label="Paginação">
    <ul class="pagination app-pagination">
        <?php if ($pager->hasPreviousPage()): ?>
            <li class="page-item"><a class="page-link" href="<?= esc($pager->getFirst(), 'attr') ?>" aria-label="Primeira página">Primeira</a></li>
            <li class="page-item"><a class="page-link" href="<?= esc($pager->getPreviousPage(), 'attr') ?>" aria-label="Página anterior">Anterior</a></li>
        <?php endif; ?>
        <?php foreach ($pager->links() as $link): ?>
            <li class="page-item<?= $link['active'] ? ' active' : '' ?>">
                <?php if ($link['active']): ?>
                    <span class="page-link" aria-current="page"><?= esc($link['title']) ?></span>
                <?php else: ?>
                    <a class="page-link" href="<?= esc($link['uri'], 'attr') ?>" aria-label="<?= esc('Página ' . $link['title'], 'attr') ?>"><?= esc($link['title']) ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        <?php if ($pager->hasNextPage()): ?>
            <li class="page-item"><a class="page-link" href="<?= esc($pager->getNextPage(), 'attr') ?>" aria-label="Próxima página">Próxima</a></li>
            <li class="page-item"><a class="page-link" href="<?= esc($pager->getLast(), 'attr') ?>" aria-label="Última página">Última</a></li>
        <?php endif; ?>
    </ul>
</nav>