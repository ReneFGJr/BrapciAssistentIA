<?php foreach (['error' => 'danger', 'success' => 'success'] as $key => $style): ?>
    <?php if ($message = session()->getFlashdata($key)): ?>
        <div class="alert alert-<?= $style ?>" role="status"><?= esc($message) ?></div>
    <?php endif; ?>
<?php endforeach; ?>