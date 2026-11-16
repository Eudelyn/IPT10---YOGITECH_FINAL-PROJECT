<?php use App\Services\ShippingService; ?>
<div class="wrap">
  <div class="page-head"><div><h1>My orders</h1><p>Each store ships its own parcel with its own tracking number.</p></div></div>
  <?= \Core\View::partial('partials/account-nav') ?>
  <br>
  <nav class="tabs" aria-label="Filter orders">
    <?php foreach ($tabs as $k => $label): ?><a class="<?= $tab === $k ? 'is-active' : '' ?>" href="<?= e(url('/orders' . ($k ? '?status=' . $k : ''))) ?>"><?= e($label) ?></a><?php endforeach; ?>
  </nav>
  <?php if (!$orders): ?>
    <div class="hud empty"><?= icon('box', 48) ?><h3>No orders here</h3><p>Orders you place show up with live tracking.</p><a class="btn" href="<?= e(url('/products')) ?>">Start shopping</a></div>
  <?php else: ?>
    <div class="hud hud--flush">
      <?php foreach ($orders as $o): ?>
        <a class="order-row" href="<?= e(url('/orders/' . $o['id'])) ?>">
          <?= product_img($o['first_thumb'], '') ?>
          <div>
            <b><?= e(str_limit($o['first_name'] ?? 'Order', 60)) ?><?= $o['item_count'] > 1 ? ' + ' . ($o['item_count'] - 1) . ' more' : '' ?></b>
            <div class="small muted"><?= e($o['order_number']) ?>, <?= e($o['store_name'] ?: $o['seller_name']) ?>, placed <?= e(date('M j, Y', strtotime($o['created_at']))) ?></div>
            <div class="small" style="margin-top:.2rem"><span class="price" style="font-size:1rem"><?= e(peso($o['total'])) ?></span>
              <?php if (!in_array($o['status'], ['delivered', 'cancelled'], true)): ?><span class="muted"> — arrives <?= e(ShippingService::estimateLabel($o['estimated_from'], $o['estimated_to'])) ?></span><?php endif; ?></div>
            <?php if ($o['status'] !== 'cancelled'): ?><div class="mini-progress"><i style="--p: <?= ShippingService::progressPercent($o['status']) ?>%"></i></div><?php endif; ?>
          </div>
          <?= status_badge($o['status']) ?>
        </a>
      <?php endforeach; ?>
    </div>
    <?= \Core\View::partial('partials/pagination', ['pager' => $pager]) ?>
  <?php endif; ?>
</div>
