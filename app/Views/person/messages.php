<?php foreach (['error' => 'danger', 'success' => 'success'] as $key => $style): ?>
    <?php if ($message = session()->getFlashdata($key)): ?>
        <?php if ($inFooter ?? false): ?>
            <span hidden data-footer-message="<?= esc($message, 'attr') ?>" data-footer-status="<?= esc($key, 'attr') ?>"></span>
            <?php break; ?>
        <?php else: ?>
            <div class="alert alert-<?= $style ?>" role="status"><?= esc($message) ?></div>
        <?php endif; ?>
    <?php endif; ?>
<?php endforeach; ?>