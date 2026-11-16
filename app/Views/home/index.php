<?php
use Core\View;
$hero = $deals[0] ?? ($newest[0] ?? null);
?>
<section class="hero">
  <div class="wrap hero__grid">
    <div>
      <h1 class="wordmark" data-glitch>YOGI<span class="row2">TECH</span></h1>
      <p class="hero__lead">Phones, laptops, tablets and accessories with local warranty. Priced in pesos, shipped nationwide, tracked to your door.</p>
      <div class="hero__cta">
        <a class="btn" href="<?= e(url('/products')) ?>"><?= icon('grid', 18) ?>Browse all gadgets</a>
        <a class="btn btn--ghost" href="<?= e(url('/track')) ?>"><?= icon('truck', 18) ?>Track a parcel</a>
      </div>
    </div>
    <?php if ($hero): ?>
      <a class="hud hero__card" href="<?= e(url('/products/' . $hero['id'])) ?>">
        <?= product_img($hero['thumbnail'], $hero['name']) ?>
        <div class="meta">
          <div><span class="small muted">Today’s biggest drop</span><b><?= e($hero['name']) ?></b></div>
          <div style="text-align:right">
            <?php if (discount_pct($hero)): ?><s class="was"><?= e(peso($hero['compare_price'])) ?></s><?php endif; ?>
            <div class="price"><?= e(peso($hero['price'])) ?></div>
          </div>
        </div>
      </a>
    <?php endif; ?>
  </div>
  <div class="wrap">
    <div class="perks">
      <div class="perk"><?= icon('truck', 26) ?><div><b>Free shipping</b><span>On <?= e(peso(config('shipping.free_threshold'), false)) ?>+ per store</span></div></div>
      <div class="perk"><?= icon('shield', 26) ?><div><b>Warranty listed</b><span>On every product page</span></div></div>
      <div class="perk"><?= icon('wallet', 26) ?><div><b>COD, GCash, card</b><span>Pay the way you like</span></div></div>
      <div class="perk"><?= icon('pin', 26) ?><div><b>Live tracking</b><span>Every step, timestamped</span></div></div>
    </div>
  </div>
</section>

<?php if ($deals): ?>
<div class="ticker" aria-label="Current deals">
  <div class="ticker__track">
    <?php for ($loop = 0; $loop < 2; $loop++): foreach ($deals as $d): ?>
      <a href="<?= e(url('/products/' . $d['id'])) ?>" <?= $loop ? 'aria-hidden="true" tabindex="-1"' : '' ?>>
        <span class="pct">−<?= discount_pct($d) ?>%</span><?= e($d['name']) ?><span class="pr"><?= e(peso($d['price'], false)) ?></span>
      </a>
    <?php endforeach; endfor; ?>
  </div>
</div>
<?php endif; ?>

<section class="section">
  <div class="wrap">
    <div class="section__head"><div><h2>Shop by category</h2></div></div>
    <div class="cats">
      <?php foreach ($categories as $c): ?>
        <a class="hud cat" href="<?= e(url('/category/' . $c['slug'])) ?>">
          <?= icon($c['icon'], 32) ?>
          <b><?= e($c['name']) ?></b>
          <span><?= e($c['description']) ?></span>
          <span class="count"><?= (int) $c['product_count'] ?> products</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($deals): ?>
<section class="section">
  <div class="wrap">
    <div class="section__head">
      <div><h2>Price drops</h2><p>Biggest discounts right now.</p></div>
      <a class="btn btn--ghost btn--sm" href="<?= e(url('/products?sort=discount')) ?>">See all deals</a>
    </div>
    <div class="grid-products">
      <?php foreach ($deals as $p) echo View::partial('partials/product-card', ['p' => $p]); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($recent): ?>
<section class="section">
  <div class="wrap">
    <div class="section__head"><div><h2>Pick up where you left off</h2></div></div>
    <div class="grid-products">
      <?php foreach ($recent as $p) echo View::partial('partials/product-card', ['p' => $p]); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="wrap">
    <div class="section__head">
      <div><h2>Top rated by buyers</h2><p>Highest review scores from verified purchases and the catalog.</p></div>
      <a class="btn btn--ghost btn--sm" href="<?= e(url('/products?sort=rating')) ?>">See more</a>
    </div>
    <div class="grid-products">
      <?php foreach ($topRated as $p) echo View::partial('partials/product-card', ['p' => $p]); ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="hud" style="display:flex;gap:2rem;align-items:center;justify-content:space-between;flex-wrap:wrap">
      <div>
        <h2 style="margin-bottom:.3rem">Where’s my parcel?</h2>
        <p class="muted" style="margin:0">Enter the tracking number from your order (starts with YGT). No sign-in needed.</p>
      </div>
      <form class="track-form" style="margin:0;flex:1;max-width:480px" action="<?= e(url('/track')) ?>" method="get">
        <label class="sr-only" for="home-track">Tracking number</label>
        <input class="input" id="home-track" name="no" placeholder="YGT…" required>
        <button class="btn btn--cyan" type="submit">Track</button>
      </form>
    </div>
  </div>
</section>

<?php if ($newest): ?>
<section class="section">
  <div class="wrap">
    <div class="section__head"><div><h2>Just added</h2></div><a class="btn btn--ghost btn--sm" href="<?= e(url('/products?sort=newest')) ?>">See all</a></div>
    <div class="grid-products">
      <?php foreach ($newest as $p) echo View::partial('partials/product-card', ['p' => $p]); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!$deals && !$newest): ?>
<section class="section"><div class="wrap"><div class="hud empty">
  <?= icon('box', 48) ?><h3>No products yet</h3>
  <p>Run <code>php yogi setup</code>, or sign in as the seller and use Import products.</p>
  <a class="btn" href="<?= e(url('/login')) ?>">Sign in as seller</a>
</div></div></section>
<?php endif; ?>
