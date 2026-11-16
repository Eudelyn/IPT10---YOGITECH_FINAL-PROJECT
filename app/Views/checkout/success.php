<?php use App\Services\ShippingService; $grand = array_sum(array_column($orders, 'total')); ?>
<div class="wrap" style="max-width:820px">
  <div class="track-hero">
    <p class="wordmark" style="font-size:clamp(2.6rem,8vw,4.5rem)" data-glitch>ORDER<span class="row2">PLACED</span></p>
    <p class="muted">We sent your order to <?= count($orders) ?> store(s). Total <?= e(peso($grand)) ?>, paid by <?= e(payment_label($orders[0]['payment_method'])) ?>.<br>A confirmation was sent to <?= e(auth()['email']) ?>.</p>
  </div>
  <?php foreach ($orders as $o): ?>
    <div class="hud" style="margin-bottom:1rem">
      <div class="hud__head"><div><h3 style="margin:0"><?= e($o['store_name']) ?></h3><span class="small muted">Order <?= e($o['order_number']) ?></span></div><?= status_badge($o['status']) ?></div>
      <div class="eta">
        <div><span>Tracking number</span><b class="big"><?= e($o['tracking_number']) ?></b></div>
        <div><span>Estimated delivery</span><b><?= e(ShippingService::estimateLabel($o['estimated_from'], $o['estimated_to'])) ?></b></div>
        <div><span>Amount</span><b><?= e(peso($o['total'])) ?></b></div>
      </div>
      <div style="display:flex;gap:.6rem;margin-top:1rem;flex-wrap:wrap">
        <a class="btn btn--cyan btn--sm" href="<?= e(url('/orders/' . $o['id'])) ?>">View order</a>
        <a class="btn btn--ghost btn--sm" href="<?= e(url('/track?no=' . $o['tracking_number'])) ?>">Open tracking page</a>
      </div>
    </div>
  <?php endforeach; ?>
  <p style="text-align:center"><a href="<?= e(url('/products')) ?>">Continue shopping</a></p>
</div>
