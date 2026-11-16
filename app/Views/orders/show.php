<?php
use App\Services\ShippingService;
use Core\View;
$o = $order;
?>
<div class="wrap">
  <nav class="breadcrumb"><a href="<?= e(url('/orders')) ?>">My orders</a><span>/</span><span><?= e($o['order_number']) ?></span></nav>
  <div class="page-head" style="margin-top:0">
    <div><h1>Order <?= e($o['order_number']) ?></h1><p>Placed <?= e(date('F j, Y, g:i A', strtotime($o['created_at']))) ?> from <a href="<?= e(url('/store/' . $o['seller_id'])) ?>"><?= e($o['store_name'] ?: $o['seller_name']) ?></a></p></div>
    <?= status_badge($o['status']) ?>
  </div>

  <?php if ($o['payment_method'] !== 'cod' && $o['payment_status'] === 'unpaid' && $o['status'] !== 'cancelled'): ?>
    <div class="notice notice--pay">
      <?= icon('wallet') ?>
      <p><b>Waiting for your <?= e(payment_label($o['payment_method'])) ?> payment.</b> Your items are reserved. The store starts preparing your order once it’s paid.</p>
      <form action="<?= e(url('/payments/checkout/' . $o['checkout_ref'] . '/pay')) ?>" method="post"><?= csrf_field() ?><button class="btn btn--sm" type="submit">Pay now</button></form>
    </div>
  <?php endif; ?>

  <section class="hud">
    <?= View::partial('partials/stepper', ['status' => $o['status']]) ?>
    <div class="eta" style="margin-top:1.2rem">
      <div><span>Tracking number</span><b class="big"><?= e($o['tracking_number']) ?></b></div>
      <div><span>Courier</span><b><?= e($o['courier'] ?: 'Assigned when shipped') ?></b></div>
      <div><span><?= $o['status'] === 'delivered' ? 'Delivered' : 'Estimated delivery' ?></span><b><?= $o['status'] === 'delivered' ? e(date('M j, Y', strtotime($o['delivered_at']))) : ($o['status'] === 'cancelled' ? '—' : e(ShippingService::estimateLabel($o['estimated_from'], $o['estimated_to']))) ?></b></div>
      <div><span>Payment</span><b><?= e(payment_label($o['payment_method'])) ?> (<?= e($o['payment_status']) ?>)</b></div>
    </div>
    <div style="display:flex;gap:.6rem;margin-top:1rem;flex-wrap:wrap">
      <?php if (ShippingService::buyerCanConfirmReceipt($o['status'])): ?>
        <form action="<?= e(url('/orders/' . $o['id'] . '/received')) ?>" method="post" data-confirm="Confirm you received this parcel in good condition?">
          <?= csrf_field() ?><button class="btn" type="submit"><?= icon('check', 18) ?>I received my order</button></form>
      <?php endif; ?>
      <a class="btn btn--ghost" href="<?= e(url('/track?no=' . $o['tracking_number'])) ?>"><?= icon('truck', 18) ?>Shareable tracking page</a>
    </div>
  </section>

  <div class="dash-grid" style="margin-top:1.25rem">
    <section class="hud">
      <h2>Tracking history</h2>
      <?= View::partial('partials/timeline', ['events' => $events]) ?>
    </section>

    <div>
      <section class="hud">
        <h3>Items</h3>
        <?php foreach ($items as $it): ?>
          <div class="list-row">
            <?= product_img($it['thumbnail'], '') ?>
            <div class="grow">
              <?php if ($it['product_id']): ?><a href="<?= e(url('/products/' . $it['product_id'])) ?>"><b><?= e($it['product_name']) ?></b></a><?php else: ?><b><?= e($it['product_name']) ?></b><?php endif; ?>
              <span class="small muted">× <?= (int) $it['quantity'] ?> at <?= e(peso($it['unit_price'])) ?></span>
              <?php if (in_array((int) $it['product_id'], $reviewable, true)): ?><br><a class="small" href="<?= e(url('/products/' . $it['product_id'] . '#reviews')) ?>">Write a review</a><?php endif; ?>
            </div>
            <b><?= e(peso($it['line_total'])) ?></b>
          </div>
        <?php endforeach; ?>
        <div style="margin-top:.8rem">
          <div class="summary-row"><span>Subtotal</span><b><?= e(peso($o['subtotal'])) ?></b></div>
          <div class="summary-row"><span>Shipping</span><b><?= $o['shipping_fee'] > 0 ? e(peso($o['shipping_fee'])) : 'Free' ?></b></div>
          <?php if ($o['discount'] > 0): ?><div class="summary-row summary-row--discount"><span>Voucher <?= e($o['voucher_code']) ?></span><b>−<?= e(peso($o['discount'])) ?></b></div><?php endif; ?>
          <div class="summary-row summary-row--total"><span>Total</span><b><?= e(peso($o['total'])) ?></b></div>
        </div>
      </section>

      <section class="hud">
        <h3>Ship to</h3>
        <p style="margin:0"><b><?= e($o['recipient_name']) ?></b>, <?= e($o['phone']) ?><br>
          <span class="muted"><?= e($o['address_line']) ?>, <?= e($o['city']) ?>, <?= e($o['province']) ?> <?= e($o['postal_code']) ?></span></p>
        <?php if ($o['notes']): ?><p class="small muted" style="margin-top:.5rem">Note: <?= e($o['notes']) ?></p><?php endif; ?>
      </section>

      <?php if (ShippingService::buyerCanCancel($o['status'])): ?>
        <form class="hud" action="<?= e(url('/orders/' . $o['id'] . '/cancel')) ?>" method="post" data-confirm="Cancel this order? Items go back to the seller's stock.">
          <?= csrf_field() ?>
          <h3>Cancel order</h3>
          <p class="small muted">You can cancel until the seller packs your parcel.</p>
          <div class="field"><label for="reason">Reason</label>
            <select class="input" id="reason" name="reason">
              <?php foreach (['Changed my mind', 'Found a better price', 'Ordered by mistake', 'Need to change address', 'Other'] as $r): ?><option><?= e($r) ?></option><?php endforeach; ?>
            </select></div>
          <button class="btn btn--danger" type="submit">Cancel order</button>
        </form>
      <?php endif; ?>
      <?php if ($o['status'] === 'cancelled'): ?>
        <section class="hud"><h3>Cancelled</h3><p class="muted" style="margin:0"><?= e($o['cancel_reason']) ?><?= $o['payment_status'] === 'refunded' ? '. Your payment is being refunded.' : '' ?></p></section>
      <?php endif; ?>
    </div>
  </div>
</div>
