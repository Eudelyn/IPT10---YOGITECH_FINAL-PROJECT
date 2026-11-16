<div class="wrap">
  <div class="page-head"><div><h1>Your cart</h1><p>Items from each store ship as a separate parcel.</p></div></div>
  <?php if (!$groups): ?>
    <div class="hud empty"><?= icon('cart', 48) ?><h3>Your cart is empty</h3><p>Find a gadget you like and add it here.</p><a class="btn" href="<?= e(url('/products')) ?>">Browse gadgets</a></div>
  <?php else: ?>
  <div class="two-col">
    <div>
      <?php foreach ($groups as $g): ?>
        <section class="hud hud--flush" style="margin-bottom:1.25rem">
          <div class="seller-group__head">
            <b><?= icon('store', 18) ?><?= e($g['store_name']) ?></b>
            <span class="small muted">Shipping: <?= $g['shipping_fee'] > 0 ? e(peso($g['shipping_fee'])) : '<span style="color:var(--green)">Free</span>' ?></span>
          </div>
          <?php foreach ($g['items'] as $it): ?>
            <div class="line-item">
              <a href="<?= e(url('/products/' . $it['id'])) ?>"><?= product_img($it['thumbnail'], $it['name']) ?></a>
              <div>
                <a class="line-item__name" href="<?= e(url('/products/' . $it['id'])) ?>"><?= e($it['name']) ?></a>
                <div class="small muted"><?= e(peso($it['price'])) ?> each<?= (int) $it['stock'] <= 5 ? ' <span style="color:var(--yellow)">— only ' . (int) $it['stock'] . ' left</span>' : '' ?></div>
                <div class="line-item__actions">
                  <form action="<?= e(url('/cart/' . $it['cart_id'] . '/update')) ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="qty" data-autosubmit>
                      <button type="button" data-step="-1" aria-label="Decrease">−</button>
                      <input type="number" name="quantity" value="<?= (int) $it['quantity'] ?>" min="1" max="<?= (int) $it['stock'] ?>" aria-label="Quantity" onchange="this.form.submit()">
                      <button type="button" data-step="1" aria-label="Increase">+</button>
                    </div>
                  </form>
                  <form action="<?= e(url('/cart/' . $it['cart_id'] . '/remove')) ?>" method="post">
                    <?= csrf_field() ?><button class="btn btn--danger btn--sm" type="submit"><?= icon('trash', 16) ?>Remove</button>
                  </form>
                </div>
              </div>
              <div class="line-item__total"><?= e(peso((float) $it['price'] * (int) $it['quantity'])) ?></div>
            </div>
          <?php endforeach; ?>
        </section>
      <?php endforeach; ?>
    </div>

    <aside class="hud sticky">
      <h2>Summary</h2>
      <div class="summary-row"><span>Subtotal</span><b><?= e(peso($subtotal)) ?></b></div>
      <div class="summary-row"><span>Shipping (<?= e(config('shipping.regions.' . $region . '.label', $region)) ?>)</span><b><?= $shipping > 0 ? e(peso($shipping)) : 'Free' ?></b></div>
      <div class="summary-row summary-row--total"><span>Estimated total</span><b><?= e(peso($subtotal + $shipping)) ?></b></div>
      <?php foreach ($groups as $g): if ($g['subtotal'] < $threshold): ?>
        <div class="progress-free">Add <?= e(peso($threshold - $g['subtotal'])) ?> from <?= e($g['store_name']) ?> for free shipping
          <i style="--w: <?= min(100, round($g['subtotal'] / $threshold * 100)) ?>%"></i></div>
      <?php endif; endforeach; ?>
      <p class="hint">Vouchers and your delivery region are applied at checkout.</p>
      <a class="btn btn--block" href="<?= e(url('/checkout')) ?>">Check out</a>
    </aside>
  </div>
  <?php endif; ?>
</div>
