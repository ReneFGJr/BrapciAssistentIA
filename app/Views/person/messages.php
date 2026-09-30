<?php foreach (['error', 'success'] as $key): ?>
    <?php if ($message = session()->getFlashdata($key)): ?>
        <span hidden data-footer-message="<?= esc($message, 'attr') ?>" data-footer-status="<?= esc($key, 'attr') ?>"></span>
        <?php break; ?>
    <?php endif; ?>
<?php endforeach; ?>
