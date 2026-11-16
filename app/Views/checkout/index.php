<?php
use App\Services\ShippingService;
$u = $user;
$v = static fn(string $k, $fallback = '') => old($k, $u[$k] ?? $fallback);
$pm = old('payment_method', 'cod');
?>
<div class="wrap">
  <div class="page-head"><div><h1>Checkout</h1><p><?= count($quote['groups']) ?> parcel(s) from <?= count($quote['groups']) ?> store(s)</p></div></div>

  <form action="<?= e(url('/checkout')) ?>" method="post" class="two-col" novalidate id="checkout-form">
    <?= csrf_field() ?>
    <input type="hidden" name="expected_total" value="<?= e(number_format((float) $quote['total'], 2, '.', '')) ?>" data-expected-total>
    <div>
      <section class="hud">
        <h2><?= icon('pin') ?> Delivery address</h2>
        <div class="grid-2">
          <div class="<?= field_class('recipient_name') ?>"><label for="recipient_name">Recipient name</label><input class="input" id="recipient_name" name="recipient_name" required value="<?= e(old('recipient_name', $u['name'])) ?>"><?= field_error('recipient_name') ?></div>
          <div class="<?= field_class('phone') ?>"><label for="phone">Mobile number</label><input class="input" id="phone" name="phone" required placeholder="09XXXXXXXXX" inputmode="tel" value="<?= e($v('phone')) ?>"><?= field_error('phone') ?></div>
        </div>
        <div class="<?= field_class('address_line') ?>"><label for="address_line">Street address</label><input class="input" id="address_line" name="address_line" required placeholder="House no., street, barangay" value="<?= e($v('address_line')) ?>"><?= field_error('address_line') ?></div>
        <div class="grid-3">
          <div class="<?= field_class('city') ?>"><label for="city">City / municipality</label><input class="input" id="city" name="city" required value="<?= e($v('city')) ?>"><?= field_error('city') ?></div>
          <div class="<?= field_class('province') ?>"><label for="province">Province</label><input class="input" id="province" name="province" required value="<?= e($v('province')) ?>"><?= field_error('province') ?></div>
          <div class="<?= field_class('postal_code') ?>"><label for="postal_code">ZIP code</label><input class="input" id="postal_code" name="postal_code" inputmode="numeric" maxlength="4" value="<?= e($v('postal_code')) ?>"><?= field_error('postal_code') ?></div>
        </div>
        <div class="<?= field_class('region') ?>">
          <label for="region">Region (sets shipping fee and delivery time)</label>
          <select class="input" id="region" name="region" data-quote-url="<?= e(url('/checkout/quote')) ?>">
            <?php foreach ($regions as $k => $r): ?><option value="<?= e($k) ?>" <?= $region === $k ? 'selected' : '' ?>><?= e($r['label']) ?> — <?= e(peso($r['fee'], false)) ?>, <?= (int) $r['min_days'] ?>–<?= (int) $r['max_days'] ?> days</option><?php endforeach; ?>
          </select>
          <?= field_error('region') ?>
        </div>
        <div class="<?= field_class('notes') ?>"><label for="notes">Delivery notes <span class="dim">(optional)</span></label><input class="input" id="notes" name="notes" maxlength="255" placeholder="Landmark, gate code, best time to deliver" value="<?= e(old('notes')) ?>"><?= field_error('notes') ?></div>
        <label class="check"><input type="checkbox" name="save_address" value="1" checked> Save this as my default address</label>
      </section>

      <section class="hud">
        <h2><?= icon('wallet') ?> Payment</h2>
        <p class="hint">Sandbox payments: no real money moves. You approve the payment on the <?= e(payment_gateway_name()) ?> page next.</p>
        <div class="pay-options">
          <label class="pay-option"><input type="radio" name="payment_method" value="cod" data-button="Place order" <?= $pm === 'cod' ? 'checked' : '' ?>><div><b>Cash on delivery</b><span>Pay the rider when your parcel arrives</span></div></label>
          <label class="pay-option"><input type="radio" name="payment_method" value="gcash" data-button="Continue to GCash" <?= $pm === 'gcash' ? 'checked' : '' ?>><div><b>GCash</b><span>Log in to GCash and approve the payment</span></div></label>
          <div class="pay-extra" id="pay-gcash"><p class="small muted" style="margin:0">After you click <b>Continue to GCash</b>, you’ll log in with your GCash number and MPIN, enter the code sent by SMS, then approve <b data-sum="total"><?= e(peso($quote['total'])) ?></b>.</p></div>
          <label class="pay-option"><input type="radio" name="payment_method" value="card" data-button="Continue to card payment" <?= $pm === 'card' ? 'checked' : '' ?>><div><b>Credit / debit card</b><span>Visa, Mastercard, JCB with 3-D Secure</span></div></label>
          <div class="pay-extra" id="pay-card"><p class="small muted" style="margin:0">You’ll enter your card on the secure payment page, then confirm with a one-time code from your bank.</p></div>
        </div>
        <?= field_error('payment_method') ?>
      </section>

      <?php foreach ($quote['groups'] as $g): ?>
        <section class="hud hud--flush" style="margin-top:1.25rem">
          <div class="seller-group__head"><b><?= icon('store', 18) ?><?= e($g['store_name']) ?></b><span class="small muted">Shipping <b data-ship-seller="<?= (int) $g['seller_id'] ?>"><?= $g['shipping_fee'] > 0 ? e(peso($g['shipping_fee'])) : 'Free' ?></b></span></div>
          <?php foreach ($g['items'] as $it): ?>
            <div class="line-item">
              <?= product_img($it['thumbnail'], $it['name']) ?>
              <div><span class="line-item__name"><?= e($it['name']) ?></span><div class="small muted">× <?= (int) $it['quantity'] ?> at <?= e(peso($it['price'])) ?></div></div>
              <div class="line-item__total"><?= e(peso((float) $it['price'] * (int) $it['quantity'])) ?></div>
            </div>
          <?php endforeach; ?>
        </section>
      <?php endforeach; ?>
    </div>

    <aside class="sticky">
      <div class="hud">
        <h2>Order summary</h2>
        <div class="summary-row"><span>Subtotal</span><b data-sum="subtotal"><?= e(peso($quote['subtotal'])) ?></b></div>
        <div class="summary-row"><span>Shipping</span><b data-sum="shipping"><?= $quote['shipping'] > 0 ? e(peso($quote['shipping'])) : 'Free' ?></b></div>
        <?php if ($quote['discount'] > 0): ?><div class="summary-row summary-row--discount"><span>Voucher <?= e($quote['voucher']['code']) ?></span><b data-sum="discount">−<?= e(peso($quote['discount'])) ?></b></div><?php endif; ?>
        <div class="summary-row summary-row--total"><span>Total</span><b data-sum="total"><?= e(peso($quote['total'])) ?></b></div>
        <div class="eta" style="margin:1rem 0"><div><span>Estimated delivery</span><b class="big" data-sum="eta"><?= e(ShippingService::estimateLabel(...$quote['estimate'])) ?></b></div></div>
        <button class="btn btn--block" type="submit" form="checkout-form" data-place-order><?= icon('check', 18) ?>Place order</button>
      </div>
    </aside>
  </form>

  <div class="two-col" style="margin-top:1.25rem">
    <section class="hud">
      <h2><?= icon('tag') ?> Voucher</h2>
      <?php if ($code && $quote['voucher']): ?>
        <div class="voucher"><span><code><?= e($code) ?></code> applied, you save <?= e(peso($quote['discount'])) ?></span>
          <form action="<?= e(url('/checkout/voucher/remove')) ?>" method="post"><?= csrf_field() ?><button class="btn btn--danger btn--sm" type="submit">Remove</button></form></div>
      <?php else: ?>
        <form class="inline-form" action="<?= e(url('/checkout/voucher')) ?>" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="region" value="<?= e($region) ?>">
          <label class="sr-only" for="code">Voucher code</label>
          <input class="input" id="code" name="code" placeholder="Enter code" style="text-transform:uppercase" value="<?= e(old('code')) ?>">
          <button class="btn btn--ghost btn--sm" type="submit">Apply</button>
        </form>
        <?= field_error('code') ?>
      <?php endif; ?>
      <div class="voucher-list">
        <?php foreach ($vouchers as $vc): ?>
          <div class="voucher"><span><code><?= e($vc['code']) ?></code> <?= e($vc['description']) ?></span><span class="dim small"><?= $vc['min_spend'] > 0 ? 'Min ' . e(peso($vc['min_spend'], false)) : 'No minimum' ?></span></div>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</div>
