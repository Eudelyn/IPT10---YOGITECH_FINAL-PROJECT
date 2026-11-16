<?php /** @var array $events */ ?>
<ol class="timeline">
  <?php foreach ($events as $ev): ?>
    <li class="<?= $ev['status'] === 'payment' ? 'is-payment' : '' ?> <?= $ev['status'] === 'cancelled' ? 'is-cancelled' : '' ?>">
      <span class="when"><?= e(date('D, M j, g:i A', strtotime($ev['created_at']))) ?></span>
      <b><?= e($ev['title']) ?></b>
      <?php if ($ev['description']): ?><p><?= e($ev['description']) ?></p><?php endif; ?>
      <?php if ($ev['location']): ?><span class="loc"><?= icon('pin', 14) ?><?= e($ev['location']) ?></span><?php endif; ?>
    </li>
  <?php endforeach; ?>
</ol>
