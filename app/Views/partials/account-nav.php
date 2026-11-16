<nav class="account-nav" aria-label="My account">
  <a class="<?= active('/dashboard') ?>" href="<?= e(url('/dashboard')) ?>"><?= icon('grid', 16) ?>Dashboard</a>
  <a class="<?= active('/orders') ?>" href="<?= e(url('/orders')) ?>"><?= icon('box', 16) ?>Orders</a>
  <a class="<?= active('/wishlist') ?>" href="<?= e(url('/wishlist')) ?>"><?= icon('heart', 16) ?>Wishlist</a>
  <a class="<?= active('/account') ?>" href="<?= e(url('/account')) ?>"><?= icon('user', 16) ?>Settings</a>
</nav>
