<?php
use App\Services\ShippingService;
use Core\View;
$o = $order;
?>
<div class="wrap" style="max-width:960px">
  <div class="track-hero">
    <h1>Track a parcel</h1>
    <p class="muted">Enter a tracking number (YGT…). Signed in as the buyer? Your order number (YT…) works too.</p>
    <form class="track-form" action="<?= e(url('/track')) ?>" method="get">
      <label class="sr-only" for="no">Tracking number</label>
      <input class="input" id="no" name="no" value="<?= e($no) ?>" placeholder="YGT…" required autofocus>
      <button class="btn btn--cyan" type="submit"><?= icon('search', 18) ?>Track</button>
    </form>
  </div>

  <?php if ($no !== '' && !$o): ?>
    <div class="hud empty"><?= icon('search', 44) ?><h3>No parcel found for <?= e($no) ?></h3><p>Check the number in your order confirmation. Tracking numbers start with YGT and have 13 characters.</p></div>
  <?php elseif ($o): ?>
    <section class="hud">
      <div class="hud__head">
        <div><span class="small muted">Tracking number</span><h2 style="margin:0"><?= e($o['tracking_number']) ?></h2></div>
        <?= status_badge($o['status']) ?>
      </div>
      <?= View::partial('partials/stepper', ['status' => $o['status']]) ?>
      <div class="eta" style="margin-top:1.2rem">
        <div><span><?= $o['status'] === 'delivered' ? 'Delivered on' : 'Estimated delivery' ?></span><b class="big"><?= $o['status'] === 'delivered' ? e(date('M j', strtotime($o['delivered_at']))) : ($o['status'] === 'cancelled' ? '—' : e(ShippingService::estimateLabel($o['estimated_from'], $o['estimated_to']))) ?></b></div>
        <div><span>Courier</span><b><?= e($o['courier'] ?: 'Not yet assigned') ?></b></div>
        <div><span>From</span><b><?= e($o['store_name'] ?: $o['seller_name']) ?></b></div>
        <div><span>To</span><b><?= e(mask_name($o['recipient_name'])) ?>, <?= e($o['city']) ?></b></div>
      </div>
    </section>
    <div class="dash-grid" style="margin-top:1.25rem">
      <section class="hud"><h3>Tracking history</h3><?= View::partial('partials/timeline', ['events' => $events]) ?></section>
      <section class="hud">
        <h3>In this parcel</h3>
        <?php foreach ($items as $it): ?>
          <div class="list-row"><?= product_img($it['thumbnail'], '') ?><div class="grow"><b><?= e($it['product_name']) ?></b><span class="small muted">× <?= (int) $it['quantity'] ?></span></div></div>
        <?php endforeach; ?>
        <?php if ($isOwner): ?><a class="btn btn--cyan btn--sm btn--block" style="margin-top:1rem" href="<?= e(url('/orders/' . $o['id'])) ?>">See full order details</a>
        <?php else: ?><p class="hint" style="margin-top:1rem">Personal details are hidden on this public page. Sign in to see the full order.</p><?php endif; ?>
        <p class="hint">Something looks wrong? Contact <?= e($o['store_name'] ?: 'the seller') ?> with your order number <?= e($o['order_number']) ?>.</p>
      </section>
    </div>
  <?php endif; ?>
</div>
