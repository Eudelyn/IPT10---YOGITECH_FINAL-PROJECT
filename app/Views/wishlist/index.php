<div class="wrap">
  <div class="page-head"><div><h1>Wishlist</h1><p>Gadgets you saved for later. Prices update live.</p></div></div>
  <?= \Core\View::partial('partials/account-nav') ?>
  <br>
  <?php if (!$products): ?>
    <div class="hud empty"><?= icon('heart', 48) ?><h3>Nothing saved yet</h3><p>Tap the heart on any product to keep track of it here.</p><a class="btn" href="<?= e(url('/products')) ?>">Browse gadgets</a></div>
  <?php else: ?>
    <div class="grid-products"><?php foreach ($products as $p) echo \Core\View::partial('partials/product-card', ['p' => $p, 'wishlist' => $wishlist]); ?></div>
  <?php endif; ?>
</div>
