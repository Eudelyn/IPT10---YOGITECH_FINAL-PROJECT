<?php use App\Services\ShippingService; $all = array_sum($counts); ?>
<div class="page-head"><div><h1>Orders</h1><p>Move each order forward. Every update appears on the buyer’s tracking page.</p></div></div>
<div class="toolbar">
  <nav class="tabs" style="margin:0;border:0;flex-wrap:wrap">
    <a class="<?= $status === '' ? 'is-active' : '' ?>" href="<?= e(url('/seller/orders')) ?>">All<span><?= $all ?></span></a>
    <?php foreach (ShippingService::LABELS as $k => $label): if (!empty($counts[$k])): ?>
      <a class="<?= $status === $k ? 'is-active' : '' ?>" href="<?= e(url('/seller/orders?status=' . $k)) ?>"><?= e($label) ?><span><?= (int) $counts[$k] ?></span></a>
    <?php endif; endforeach; ?>
  </nav>
  <form class="inline-form" method="get" action="<?= e(url('/seller/orders')) ?>">
    <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <label class="sr-only" for="oq">Search orders</label>
    <input class="input" id="oq" name="q" value="<?= e($q) ?>" placeholder="Order, tracking no. or name">
    <button class="btn btn--quiet btn--sm" type="submit"><?= icon('search', 16) ?></button>
  </form>
</div>
<div class="hud hud--flush">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Order</th><th>Buyer</th><th>Ship to</th><th>Payment</th><th>Status</th><th class="num">Items</th><th class="num">Total</th></tr></thead>
      <tbody>
      <?php if (!$orders): ?><tr><td colspan="7" class="muted" style="padding:2rem;text-align:center">No orders in this view.</td></tr><?php endif; ?>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><a href="<?= e(url('/seller/orders/' . $o['id'])) ?>"><b><?= e($o['order_number']) ?></b></a><br><span class="small dim"><?= e(date('M j, g:i A', strtotime($o['created_at']))) ?></span></td>
          <td><?= e($o['buyer_name']) ?></td>
          <td><?= e($o['city']) ?><br><span class="small dim"><?= e($o['region']) ?></span></td>
          <td><?= e(payment_label($o['payment_method'])) ?><br><span class="small <?= $o['payment_status'] === 'paid' ? '' : 'dim' ?>" style="<?= $o['payment_status'] === 'paid' ? 'color:var(--green)' : '' ?>"><?= e(ucfirst($o['payment_status'])) ?></span></td>
          <td><?= status_badge($o['status']) ?></td>
          <td class="num"><?= (int) $o['qty'] ?></td>
          <td class="num"><?= e(peso($o['total'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= \Core\View::partial('partials/pagination', ['pager' => $pager]) ?>
</div>
