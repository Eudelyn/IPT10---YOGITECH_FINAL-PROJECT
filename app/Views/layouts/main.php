<?php $me = auth(); $isBuyer = $me && $me['role'] === 'buyer'; $fx = fx_info(); ?>
<!doctype html>
<html lang="en">
<head><?= \Core\View::partial('partials/head', ['title' => $title ?? null]) ?></head>
<body>
<a class="sr-only" href="#main">Skip to content</a>
<div class="topbar">
  <div class="wrap">
    <span>Free shipping on orders <?= e(peso(config('shipping.free_threshold'), false)) ?>+ per store, nationwide</span>
    <span class="hide-sm">
      <a href="<?= e(url('/track')) ?>">Track a parcel</a>
      <?php if (!$me && config('app.seller_signup')): ?>&nbsp;&nbsp;<a href="<?= e(url('/register?role=seller')) ?>">Sell on Yogitech</a><?php endif; ?>
    </span>
  </div>
</div>

<header class="header">
  <div class="wrap header__row">
    <a class="logo" href="<?= e(url('/')) ?>" aria-label="Yogitech home"><img class="logo__mark" src="<?= e(asset('assets/img/logo.svg')) ?>" alt="" width="40" height="40"><span>YOGI<span class="y">TECH</span></span></a>

    <form class="search" action="<?= e(url('/products')) ?>" method="get" role="search" autocomplete="off">
      <?= icon('search', 18) ?>
      <label class="sr-only" for="search-input">Search gadgets</label>
      <input class="input" id="search-input" name="q" type="search" placeholder="Search phones, laptops, brands…"
             value="<?= e(is_string($_GET['q'] ?? null) ? $_GET['q'] : '') ?>" data-endpoint="<?= e(url('/api/products')) ?>">
      <div class="search__results" id="search-results"></div>
    </form>

    <nav class="header__actions" aria-label="Account">
      <a class="header__link" href="<?= e(url('/track')) ?>"><?= icon('truck') ?><span class="lbl">Track</span></a>
      <?php if ($isBuyer): ?>
        <a class="header__link" href="<?= e(url('/wishlist')) ?>" aria-label="Wishlist"><?= icon('heart') ?></a>
        <a class="header__link" href="<?= e(url('/cart')) ?>" aria-label="Cart, <?= cart_count() ?> items"><?= icon('cart') ?>
          <?php if (cart_count() > 0): ?><span class="badge-count"><?= cart_count() ?></span><?php endif; ?></a>
      <?php endif; ?>
      <?php if ($me): ?>
        <div class="dropdown" data-dropdown>
          <button class="header__link icon-btn" type="button" data-dropdown-toggle aria-expanded="false" aria-haspopup="true">
            <?= icon('user') ?><span class="lbl"><?= e(explode(' ', $me['name'])[0]) ?></span>
          </button>
          <div class="dropdown__menu">
            <div class="dropdown__who"><b><?= e($me['name']) ?></b><span class="small muted"><?= e($me['role'] === 'seller' ? ($me['store_name'] ?: 'Seller') : 'Buyer account') ?></span></div>
            <?php if ($me['role'] === 'seller'): ?>
              <a href="<?= e(url('/seller')) ?>"><?= icon('chart', 18) ?>Seller center</a>
              <a href="<?= e(url('/seller/orders')) ?>"><?= icon('box', 18) ?>Orders to fulfil</a>
              <a href="<?= e(url('/store/' . $me['id'])) ?>"><?= icon('store', 18) ?>View my store</a>
            <?php else: ?>
              <a href="<?= e(url('/dashboard')) ?>"><?= icon('grid', 18) ?>Dashboard</a>
              <a href="<?= e(url('/orders')) ?>"><?= icon('box', 18) ?>My orders</a>
              <a href="<?= e(url('/wishlist')) ?>"><?= icon('heart', 18) ?>Wishlist</a>
            <?php endif; ?>
            <a href="<?= e(url('/account')) ?>"><?= icon('user', 18) ?>Account settings</a>
            <hr>
            <form action="<?= e(url('/logout')) ?>" method="post"><?= csrf_field() ?><button type="submit"><?= icon('logout', 18) ?>Sign out</button></form>
          </div>
        </div>
      <?php else: ?>
        <a class="header__link" href="<?= e(url('/login')) ?>"><?= icon('user') ?><span class="lbl">Sign in</span></a>
        <a class="btn btn--sm" href="<?= e(url('/register')) ?>">Create account</a>
      <?php endif; ?>
    </nav>
  </div>
  <nav class="nav" aria-label="Categories">
    <div class="wrap">
      <a class="<?= active('/products') ?>" href="<?= e(url('/products')) ?>"><?= icon('grid', 16) ?>All gadgets</a>
      <?php foreach (nav_categories() as $c): ?>
        <a class="<?= active('/category/' . $c['slug']) ?>" href="<?= e(url('/category/' . $c['slug'])) ?>"><?= icon($c['icon'], 16) ?><?= e($c['name']) ?></a>
      <?php endforeach; ?>
      <a class="nav__deal" href="<?= e(url('/products?sort=discount')) ?>"><?= icon('bolt', 16) ?>Deals</a>
    </div>
  </nav>
</header>

<?= \Core\View::partial('partials/flash') ?>

<main id="main"><?= $content ?></main>

<footer class="footer">
  <div class="wrap footer__grid">
    <div>
      <a class="logo" href="<?= e(url('/')) ?>"><img class="logo__mark" src="<?= e(asset('assets/img/logo.svg')) ?>" alt="" width="40" height="40"><span>YOGI<span class="y">TECH</span></span></a>
      <p class="muted small" style="margin-top:1rem;max-width:36ch">Authentic gadgets with local warranty, priced in pesos and tracked from warehouse to doorstep.</p>
    </div>
    <div>
      <h4>Shop</h4>
      <?php foreach (nav_categories() as $c): ?><a href="<?= e(url('/category/' . $c['slug'])) ?>"><?= e($c['name']) ?></a><?php endforeach; ?>
    </div>
    <div>
      <h4>Help</h4>
      <a href="<?= e(url('/track')) ?>">Track a parcel</a>
      <a href="<?= e(url('/orders')) ?>">My orders</a>
      <a href="<?= e(url('/cart')) ?>">Cart</a>
    </div>
    <div>
      <h4>Sell</h4>
      <?php if (config('app.seller_signup')): ?><a href="<?= e(url('/register?role=seller')) ?>">Open a store</a><?php endif; ?>
      <a href="<?= e(url('/seller')) ?>">Seller center</a>
    </div>
  </div>
  <div class="wrap footer__bottom">
    <span>© <?= date('Y') ?> Yogitech. Built on the Yogi PHP framework.</span>
    <span class="fx-chip <?= ($fx['source'] ?? '') === 'live' ? '' : 'is-fallback' ?>" title="<?= e($fx['provider'] ?? '') ?>">
      <i></i>Prices in PHP. US$1 = <?= e(peso($fx['rate'])) ?> (<?= ($fx['source'] ?? '') === 'live' ? 'live rate' : 'offline rate' ?>)
    </span>
  </div>
</footer>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
