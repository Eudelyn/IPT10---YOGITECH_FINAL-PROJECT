<?php
use App\Services\ShippingService;
use Core\View;
$o = $order;
$hints = [
  'confirmed' => 'Tell the buyer you accepted the order.',
  'packed' => 'The parcel is sealed and labelled.',
  'shipped' => 'Handed to the courier. Pick which one.',
  'in_transit' => 'Add a hub or sorting-center update.',
  'out_for_delivery' => 'Rider is delivering today.',
  'delivered' => 'Buyer received the parcel. COD is marked collected.',
  'cancelled' => 'Stock is returned and paid orders are refunded.',
];
?>
<nav class="breadcrumb"><a href="<?= e(url('/seller/orders')) ?>">Orders</a><span>/</span><span><?= e($o['order_number']) ?></span></nav>
<div class="page-head" style="margin-top:0">
  <div><h1>Order <?= e($o['order_number']) ?></h1><p>Tracking <?= e($o['tracking_number']) ?>, placed <?= e(date('M j, Y g:i A', strtotime($o['created_at']))) ?></p></div>
  <?= status_badge($o['status']) ?>
</div>

<section class="hud"><?= View::partial('partials/stepper', ['status' => $o['status']]) ?></section>

<div class="dash-grid" style="margin-top:1.25rem">
  <div>
    <?php if ($next): ?>
    <form class="hud" action="<?= e(url('/seller/orders/' . $o['id'] . '/status')) ?>" method="post" novalidate>
      <?= csrf_field() ?>
      <h2>Update status</h2>
      <div class="field"><label for="status">Next step</label>
        <select class="input" id="status" name="status" data-status-select>
          <?php foreach ($next as $s): ?><option value="<?= e($s) ?>"><?= e($s === 'in_transit' && $o['status'] === 'in_transit' ? 'Add transit update' : ShippingService::label($s)) ?> — <?= e($hints[$s] ?? '') ?></option><?php endforeach; ?>
        </select></div>
      <div class="field" data-for-status="shipped"><label for="courier">Courier</label>
        <select class="input" id="courier" name="courier"><?php foreach ($couriers as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select></div>
      <div class="field" data-for-status="in_transit,out_for_delivery"><label for="location">Location <span class="dim">(optional)</span></label>
        <input class="input" id="location" name="location" maxlength="120" placeholder="e.g. Ninja Van — Cebu sorting hub"></div>
      <div class="field" data-for-status="cancelled"><label for="reason">Cancellation reason</label>
        <input class="input" id="reason" name="reason" maxlength="150" placeholder="e.g. Item out of stock"></div>
      <div class="field" data-for-status="confirmed,packed,shipped,in_transit,out_for_delivery,delivered"><label for="note">Note to buyer <span class="dim">(optional)</span></label>
        <input class="input" id="note" name="note" maxlength="150" placeholder="Shown on the tracking timeline"></div>
      <button class="btn" type="submit">Save update</button>
    </form>
    <?php else: ?>
      <section class="hud"><h2>No further steps</h2><p class="muted" style="margin:0">This order is <?= e(strtolower(ShippingService::label($o['status']))) ?>.</p></section>
    <?php endif; ?>
    <section class="hud"><h2>Tracking history</h2><?= View::partial('partials/timeline', ['events' => $events]) ?></section>
  </div>

  <div>
    <section class="hud">
      <h3>Items to pack</h3>
      <?php foreach ($items as $it): ?>
        <div class="list-row"><?= product_img($it['thumbnail'], '') ?><div class="grow"><b><?= e($it['product_name']) ?></b><span class="small muted">× <?= (int) $it['quantity'] ?> at <?= e(peso($it['unit_price'])) ?></span></div><b><?= e(peso($it['line_total'])) ?></b></div>
      <?php endforeach; ?>
      <div style="margin-top:.8rem">
        <div class="summary-row"><span>Subtotal</span><b><?= e(peso($o['subtotal'])) ?></b></div>
        <div class="summary-row"><span>Shipping</span><b><?= e(peso($o['shipping_fee'])) ?></b></div>
        <?php if ($o['discount'] > 0): ?><div class="summary-row summary-row--discount"><span>Platform voucher <?= e($o['voucher_code']) ?></span><b>−<?= e(peso($o['discount'])) ?></b></div><?php endif; ?>
        <div class="summary-row summary-row--total"><span>Total</span><b><?= e(peso($o['total'])) ?></b></div>
      </div>
    </section>
    <section class="hud">
      <h3>Buyer and delivery</h3>
      <p style="margin:0"><b><?= e($o['recipient_name']) ?></b>, <?= e($o['phone']) ?><br>
      <span class="muted"><?= e($o['address_line']) ?>, <?= e($o['city']) ?>, <?= e($o['province']) ?> <?= e($o['postal_code']) ?> (<?= e($o['region']) ?>)</span></p>
      <?php if ($o['notes']): ?><p class="small" style="margin-top:.5rem;color:var(--yellow)">Note: <?= e($o['notes']) ?></p><?php endif; ?>
      <p class="small muted" style="margin-top:.8rem">Payment: <?= e(payment_label($o['payment_method'])) ?>, <?= e($o['payment_status']) ?><br>Account: <?= e($o['buyer_name']) ?> (<?= e($o['buyer_email']) ?>)</p>
    </section>
  </div>
</div>
