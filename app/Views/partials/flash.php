<?php $types = ['success' => 'check', 'error' => 'x', 'info' => 'bolt']; ?>
<div class="flashes" aria-live="polite">
  <?php foreach ($types as $type => $ico): if ($msg = flash($type)): ?>
    <div class="flash flash--<?= $type ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
      <?= icon($ico, 18) ?><p><?= e($msg) ?></p>
      <button class="icon-btn" type="button" aria-label="Dismiss"><?= icon('x', 16) ?></button>
    </div>
  <?php endif; endforeach; ?>
</div>
