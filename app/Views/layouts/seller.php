<?php $me = auth(); $path = rtrim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/'); ?>
<!doctype html>
<html lang="en">
<head><?= \Core\View::partial('partials/head', ['title' => ($title ?? 'Seller center')]) ?></head>
<body>
<?= \Core\View::partial('partials/flash') ?>
<div class="seller-shell">
  <aside class="sidebar">
    <a class="logo" href="<?= e(url('/')) ?>"><img class="logo__mark" src="<?= e(asset('assets/img/logo.svg')) ?>" alt="" width="40" height="40"><span>YOGI<span class="y">TECH</span></span></a>
    <div class="sidebar__store">
      <span class="small muted">Seller center</span>
      <b><?= e($me['store_name'] ?: $me['name']) ?></b>
      <a class="small" href="<?= e(url('/store/' . $me['id'])) ?>">View storefront</a>
    </div>
    <nav aria-label="Seller">
      <a class="<?= substr($path, -7) === '/seller' ? 'is-active' : '' ?>" href="<?= e(url('/seller')) ?>"><?= icon('chart', 18) ?>Dashboard</a>
      <a class="<?= active('/seller/orders') ?>" href="<?= e(url('/seller/orders')) ?>"><?= icon('box', 18) ?>Orders</a>
      <?php $onCreate = substr($path, -16) === '/products/create'; ?>
      <a class="<?= $onCreate ? '' : active('/seller/products') ?>" href="<?= e(url('/seller/products')) ?>"><?= icon('tag', 18) ?>Products</a>
      <a class="<?= $onCreate ? 'is-active' : '' ?>" href="<?= e(url('/seller/products/create')) ?>"><?= icon('plus', 18) ?>Add product</a>
      <a class="<?= active('/seller/import') ?>" href="<?= e(url('/seller/import')) ?>"><?= icon('download', 18) ?>Import products</a>
      <a class="<?= active('/account') ?>" href="<?= e(url('/account')) ?>"><?= icon('store', 18) ?>Store settings</a>
    </nav>
    <div class="sidebar__foot">
      <form action="<?= e(url('/logout')) ?>" method="post"><?= csrf_field() ?><button class="btn btn--quiet btn--sm btn--block" type="submit"><?= icon('logout', 16) ?>Sign out</button></form>
    </div>
  </aside>
  <main class="seller-main" id="main"><?= $content ?></main>
</div>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
