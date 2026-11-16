<?php
use App\Services\ShippingService;
/** @var string $status */
$idx = ShippingService::progressIndex($status);
$icons = ['pending' => 'cart', 'confirmed' => 'check', 'packed' => 'box', 'shipped' => 'truck', 'out_for_delivery' => 'pin', 'delivered' => 'shield'];
?>
<?php if ($status === 'cancelled'): ?>
  <div class="notice" style="border-color:var(--magenta);background:var(--magenta-dim)"><?= icon('x') ?><p>This order was cancelled.</p></div>
<?php else: ?>
<div class="stepper" style="--p: <?= ShippingService::progressPercent($status) ?>" role="list" aria-label="Order progress">
  <span class="stepper__fill"></span>
  <?php foreach (ShippingService::MILESTONES as $i => $m): ?>
    <div class="step <?= $i < $idx ? 'is-done' : ($i === $idx ? 'is-current' : '') ?>" role="listitem" <?= $i === $idx ? 'aria-current="step"' : '' ?>>
      <span class="step__dot"><?= icon($icons[$m], 16) ?></span>
      <span><?= e(ShippingService::label($m)) ?></span>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>
