<?php $max = max(1, max(array_column($chart, 'amount'))); $week = array_sum(array_column($chart, 'amount')); ?>
<div class="page-head">
  <div><h1><?= e($user['store_name'] ?: 'Your store') ?></h1><p><?= e(date('l, F j')) ?>. Here’s what needs your attention.</p></div>
</div>

<?php if ((int) $stats['to_ship'] > 0): ?>
  <div class="notice"><?= icon('box') ?><p><b><?= (int) $stats['to_ship'] ?> order(s)</b> waiting to be confirmed, packed or handed to a courier.</p><a class="btn btn--sm" href="<?= e(url('/seller/orders?status=pending')) ?>">Process orders</a></div>
<?php endif; ?>
<?php if ((int) $products['total'] === 0): ?>
  <div class="notice"><?= icon('tag') ?><p>Your store has no products yet. Import ready-made products or add your own.</p><a class="btn btn--sm" href="<?= e(url('/seller/import')) ?>">Import products</a></div>
<?php endif; ?>

<div class="stats">
  <div class="hud stat stat--yellow"><span><?= icon('wallet', 16) ?>Completed sales</span><b><?= e(peso($stats['revenue'], false)) ?></b><small><?= (int) $stats['delivered'] ?> delivered orders</small></div>
  <div class="hud stat stat--cyan"><span><?= icon('truck', 16) ?>In progress</span><b><?= e(peso($stats['pipeline'], false)) ?></b><small><?= (int) $stats['to_ship'] ?> to ship, <?= (int) $stats['in_transit'] ?> in transit</small></div>
  <div class="hud stat"><span><?= icon('box', 16) ?>Orders this month</span><b><?= (int) $stats['this_month'] ?></b><small>Excluding cancelled</small></div>
  <div class="hud stat stat--magenta"><span><?= icon('tag', 16) ?>Products</span><b><?= (int) $products['active'] ?>/<?= (int) $products['total'] ?></b><small><?= (int) $products['low'] ?> low on stock</small></div>
</div>

<div class="dash-grid">
  <section class="hud">
    <div class="hud__head"><h2>Sales, last 7 days</h2><span class="price"><?= e(peso($week, false)) ?></span></div>
    <div class="chart" role="img" aria-label="Daily sales for the last 7 days">
      <?php foreach ($chart as $d): ?>
        <div class="chart__col">
          <div class="chart__bar" style="--h: <?= round($d['amount'] / $max * 100) ?>%" data-v="<?= e(peso($d['amount'], false)) ?>, <?= $d['orders'] ?> order(s)"></div>
          <span><?= e($d['label']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="hud">
    <div class="hud__head"><h3>Low stock</h3><a class="small" href="<?= e(url('/seller/products?filter=low')) ?>">See all</a></div>
    <?php if (!$lowStock): ?><p class="muted">All products have healthy stock.</p><?php endif; ?>
    <?php foreach ($lowStock as $p): ?>
      <a class="list-row" href="<?= e(url('/seller/products/' . $p['id'] . '/edit')) ?>">
        <?= product_img($p['thumbnail'], '') ?>
        <div class="grow"><b><?= e($p['name']) ?></b></div>
        <span class="pill <?= (int) $p['stock'] === 0 ? 'pill--low' : 'pill--draft' ?>"><?= (int) $p['stock'] ?> left</span>
      </a>
    <?php endforeach; ?>
  </section>
</div>

<div class="dash-grid" style="margin-top:1.25rem">
  <section class="hud hud--flush">
    <div class="hud__head" style="padding:1.2rem 1.2rem 0"><h2>Recent orders</h2><a class="small" href="<?= e(url('/seller/orders')) ?>">All orders</a></div>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Order</th><th>Buyer</th><th>Status</th><th class="num">Total</th></tr></thead>
        <tbody>
        <?php if (!$recent): ?><tr><td colspan="4" class="muted">No orders yet. They’ll appear here as soon as a buyer checks out.</td></tr><?php endif; ?>
        <?php foreach ($recent as $o): ?>
          <tr>
            <td><a href="<?= e(url('/seller/orders/' . $o['id'])) ?>"><b><?= e($o['order_number']) ?></b></a><br><span class="small dim"><?= e(time_ago($o['created_at'])) ?></span></td>
            <td><?= e($o['buyer_name']) ?><br><span class="small dim"><?= e($o['city']) ?></span></td>
            <td><?= status_badge($o['status']) ?></td>
            <td class="num"><?= e(peso($o['total'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
  <section class="hud">
    <h3>Best sellers</h3>
    <?php if (!$top): ?><p class="muted">No sales yet.</p><?php endif; ?>
    <?php foreach ($top as $p): ?>
      <div class="list-row"><?= product_img($p['thumbnail'], '') ?><div class="grow"><b><?= e($p['name']) ?></b><span class="small muted"><?= e(peso($p['price'], false)) ?></span></div><span class="pill"><?= (int) $p['sold_count'] ?> sold</span></div>
    <?php endforeach; ?>
  </section>
</div>
