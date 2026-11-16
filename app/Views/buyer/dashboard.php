<?php
use App\Services\ShippingService;
use Core\View;
$first = explode(' ', $user['name'])[0];
?>
<div class="wrap">
  <div class="page-head">
    <div><h1>Hi, <?= e($first) ?></h1><p>Your orders, parcels and saved gadgets.</p></div>
    <a class="btn" href="<?= e(url('/products')) ?>"><?= icon('grid', 18) ?>Keep shopping</a>
  </div>
  <?= View::partial('partials/account-nav') ?>
  <br>
  <?php if (!$hasAddress): ?>
    <div class="notice"><?= icon('pin') ?><p>Add your delivery address and mobile number so checkout takes seconds.</p><a class="btn btn--sm" href="<?= e(url('/account')) ?>">Add address</a></div>
  <?php endif; ?>

  <div class="stats">
    <div class="hud stat stat--cyan"><span><?= icon('truck', 16) ?>On the way</span><b><?= (int) $stats['active'] ?></b><small>Active orders</small></div>
    <div class="hud stat"><span><?= icon('box', 16) ?>Delivered</span><b><?= (int) $stats['delivered'] ?></b><small>Completed orders</small></div>
    <div class="hud stat stat--yellow"><span><?= icon('wallet', 16) ?>Total spent</span><b><?= e(peso($stats['spent'], false)) ?></b><small>Excluding cancelled</small></div>
    <div class="hud stat stat--magenta"><span><?= icon('heart', 16) ?>Wishlist</span><b><?= (int) $wishCount ?></b><small><a href="<?= e(url('/wishlist')) ?>">View saved</a></small></div>
  </div>

  <div class="dash-grid">
    <section class="hud hud--flush">
      <div class="hud__head" style="padding:1.2rem 1.2rem 0"><h2>Parcels on the way</h2><a class="small" href="<?= e(url('/orders?status=active')) ?>">All active orders</a></div>
      <?php if (!$active): ?>
        <div class="empty"><?= icon('truck', 40) ?><h3>Nothing in transit</h3><p>When you order, every step from packing to delivery shows up here.</p></div>
      <?php endif; ?>
      <?php foreach ($active as $o): ?>
        <a class="order-row" href="<?= e(url('/orders/' . $o['id'])) ?>">
          <?= product_img($o['first_thumb'], '') ?>
          <div>
            <b><?= e(str_limit($o['first_name'] ?? 'Order', 48)) ?><?= $o['item_count'] > 1 ? ' + ' . ($o['item_count'] - 1) . ' more' : '' ?></b>
            <div class="small muted"><?= e($o['store_name'] ?: $o['seller_name']) ?>, arrives <?= e(ShippingService::estimateLabel($o['estimated_from'], $o['estimated_to'])) ?></div>
            <div class="mini-progress"><i style="--p: <?= ShippingService::progressPercent($o['status']) ?>%"></i></div>
          </div>
          <?= status_badge($o['status']) ?>
        </a>
      <?php endforeach; ?>
    </section>

    <section class="hud">
      <div class="hud__head"><h3>Recent orders</h3><a class="small" href="<?= e(url('/orders')) ?>">See all</a></div>
      <?php if (!$recentOrders): ?><p class="muted">No orders yet.</p><?php endif; ?>
      <?php foreach ($recentOrders as $o): ?>
        <a class="list-row" href="<?= e(url('/orders/' . $o['id'])) ?>">
          <div class="grow"><b><?= e($o['order_number']) ?></b><span class="small muted"><?= e(date('M j', strtotime($o['created_at']))) ?>, <?= e(peso($o['total'])) ?></span></div>
          <?= status_badge($o['status']) ?>
        </a>
      <?php endforeach; ?>
      <form class="track-form" style="margin-top:1rem" action="<?= e(url('/track')) ?>" method="get">
        <label class="sr-only" for="dash-track">Tracking number</label>
        <input class="input" id="dash-track" name="no" placeholder="Tracking no." required>
        <button class="btn btn--cyan btn--sm" type="submit">Track</button>
      </form>
    </section>
  </div>

  <section class="section">
    <div class="section__head"><div><h2>Picked for you</h2><p>Based on the categories you’ve bought from.</p></div></div>
    <div class="grid-products"><?php foreach ($recommended as $p) echo View::partial('partials/product-card', ['p' => $p]); ?></div>
  </section>

  <?php if ($recent): ?>
  <section class="section" style="padding-top:0">
    <div class="section__head"><h2>Recently viewed</h2></div>
    <div class="grid-products"><?php foreach ($recent as $p) echo View::partial('partials/product-card', ['p' => $p]); ?></div>
  </section>
  <?php endif; ?>
</div>
