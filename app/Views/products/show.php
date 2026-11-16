<?php
use App\Services\ShippingService;
use Core\View;
$p = $product;
$off = discount_pct($p);
$stock = (int) $p['stock'];
$me = auth();
$total = array_sum($breakdown);
?>
<div class="wrap">
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= e(url('/')) ?>">Home</a><span>/</span>
    <a href="<?= e(url('/category/' . $p['category_slug'])) ?>"><?= e($p['category_name']) ?></a><span>/</span>
    <span><?= e(str_limit($p['name'], 40)) ?></span>
  </nav>

  <?php if ($p['status'] !== 'active'): ?>
    <div class="notice"><?= icon('edit') ?><p>This product is a draft. Only you can see it.</p></div>
  <?php endif; ?>

  <div class="pdp">
    <?php $video = product_video($p['video_url'] ?? null); ?>
    <div class="hud hud--flush" data-gallery>
      <div class="gallery__main">
        <?php if ($video): ?>
          <div class="gallery__video" data-gallery-video>
            <?php if ($video['type'] === 'youtube'): ?>
              <button type="button" class="gallery__facade" data-yt-src="<?= e($video['src'] . '&autoplay=1') ?>" data-yt-title="<?= e($p['name']) ?> video" aria-label="Play <?= e($p['name']) ?> video">
                <img src="<?= e($video['poster']) ?>" alt="" data-fallbacks="<?= e(img_url($images[0])) ?>|<?= e(url('assets/img/placeholder.svg')) ?>" onerror="var l=(this.dataset.fallbacks||'').split('|').filter(Boolean);if(l.length){this.src=l.shift();this.dataset.fallbacks=l.join('|');this.classList.add('is-fallback')}else{this.onerror=null}">
                <span class="gallery__bigplay" aria-hidden="true"><svg viewBox="0 0 24 24" width="30" height="30"><path d="M8 5v14l11-7z" fill="currentColor"/></svg></span>
                <span class="gallery__label">Watch video</span>
              </button>
            <?php else: ?>
              <video src="<?= e($video['src']) ?>" controls playsinline preload="metadata" poster="<?= e(img_url($images[0])) ?>"></video>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <?= product_img($images[0], $p['name'], 'data-gallery-main' . ($video ? ' hidden' : '')) ?>
      </div>
      <?php if ($video): ?>
        <p class="gallery__note small muted">Video won’t play? <a href="<?= e($video['watch']) ?>" target="_blank" rel="noopener">Watch it on <?= $video['type'] === 'youtube' ? 'YouTube' : 'a new tab' ?></a>.</p>
      <?php endif; ?>
      <?php if (count($images) > 1 || $video): ?>
        <div class="gallery__thumbs">
          <?php if ($video): ?>
            <button type="button" class="is-active gallery__vthumb" data-show-video aria-label="Play video">
              <?php if (!empty($video['poster'])): ?><img src="<?= e($video['poster']) ?>" alt="" loading="lazy"><?php else: ?><?= product_img($images[0], '') ?><?php endif; ?>
              <span class="gallery__play" aria-hidden="true">▶</span>
            </button>
          <?php endif; ?>
          <?php foreach ($images as $i => $img): ?>
            <button type="button" class="<?= !$video && $i === 0 ? 'is-active' : '' ?>" data-src="<?= e(img_url($img)) ?>" aria-label="Show image <?= $i + 1 ?>"><?= product_img($img, '') ?></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div>
      <span class="pdp__brand"><?= e($p['brand']) ?></span>
      <h1 class="pdp__title"><?= e($p['name']) ?></h1>
      <div class="pdp__stats">
        <a href="#reviews"><span class="rating"><?= stars((float) $p['rating']) ?></span> <?= number_format((float) $p['rating'], 1) ?> (<?= (int) $p['rating_count'] ?> reviews)</a>
        <span><?= (int) $p['sold_count'] ?> sold</span>
        <?php if ($p['sku']): ?><span class="dim">SKU <?= e($p['sku']) ?></span><?php endif; ?>
      </div>

      <div class="pdp__price">
        <span class="price"><?= e(peso($p['price'])) ?></span>
        <?php if ($off): ?><s class="was"><?= e(peso($p['compare_price'])) ?></s><span class="off">Save <?= $off ?>%</span><?php endif; ?>
      </div>

      <p class="stock <?= $stock === 0 ? 'stock--out' : ($stock <= 5 ? 'stock--low' : 'stock--ok') ?>">
        <?= $stock === 0 ? 'Out of stock' : ($stock <= 5 ? 'Only ' . $stock . ' left' : 'In stock (' . $stock . ' available)') ?>
      </p>

      <?php if (!$me || $me['role'] === 'buyer'): ?>
        <form action="<?= e(url('/cart/add')) ?>" method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
          <div class="label" style="margin-bottom:.4rem">Quantity</div>
          <div class="qty">
            <button type="button" data-step="-1" aria-label="Decrease quantity">−</button>
            <input type="number" name="quantity" value="1" min="1" max="<?= max(1, $stock) ?>" aria-label="Quantity">
            <button type="button" data-step="1" aria-label="Increase quantity">+</button>
          </div>
          <div class="buy-row">
            <button class="btn" type="submit" <?= $stock === 0 ? 'disabled' : '' ?>><?= icon('cart', 18) ?>Add to cart</button>
            <button class="btn btn--cyan" type="submit" name="buy_now" value="1" <?= $stock === 0 ? 'disabled' : '' ?>><?= icon('bolt', 18) ?>Buy now</button>
            <button class="btn btn--quiet js-wish <?= $saved ? 'is-saved' : '' ?>" type="button" style="flex:0 0 auto"
                    data-url="<?= e(url('/wishlist/' . $p['id'] . '/toggle')) ?>" data-login="<?= e(url('/login')) ?>" <?= $me ? '' : 'data-guest="' . e(url('/login')) . '"' ?>
                    aria-pressed="<?= $saved ? 'true' : 'false' ?>"><?= icon('heart', 18) ?><span class="lbl"><?= $saved ? 'Saved' : 'Save' ?></span></button>
          </div>
        </form>
      <?php elseif ((int) $me['id'] === (int) $p['seller_id']): ?>
        <div class="buy-row"><a class="btn btn--cyan" href="<?= e(url('/seller/products/' . $p['id'] . '/edit')) ?>"><?= icon('edit', 18) ?>Edit this product</a></div>
      <?php else: ?>
        <p class="muted small">You’re signed in as a seller. Use a buyer account to purchase.</p>
      <?php endif; ?>

      <div class="info-list">
        <div><?= icon('truck') ?><div>
          <b>Delivery to
            <label class="sr-only" for="ship-region">Region</label>
            <select id="ship-region" class="input" style="display:inline-block;width:auto;padding:.2rem 2rem .2rem .5rem;font-size:.9rem"
                    data-shipping-url="<?= e(url('/api/shipping-quote')) ?>" data-subtotal="<?= e($p['price']) ?>">
              <?php foreach (ShippingService::regions() as $key => $r): ?><option value="<?= e($key) ?>" <?= $region === $key ? 'selected' : '' ?>><?= e($r['label']) ?></option><?php endforeach; ?>
            </select></b>
          <span>Arrives <b data-ship-eta style="display:inline"><?= e($eta) ?></b>. Shipping: <b data-ship-fee style="display:inline"><?= $fee > 0 ? e(peso($fee)) : 'Free' ?></b></span>
        </div></div>
        <div><?= icon('shield') ?><div><b>Warranty</b><span><?= e($p['warranty'] ?: 'Seller warranty') ?></span></div></div>
        <div><?= icon('return') ?><div><b>Returns</b><span><?= e($p['return_policy'] ?: '7 days return policy') ?></span></div></div>
        <div><?= icon('store') ?><div><b>Sold by <a href="<?= e(url('/store/' . $p['seller_id'])) ?>"><?= e($p['store_name'] ?: $p['seller_name']) ?></a></b><span>Ships from <?= e($p['seller_city'] ?: 'the Philippines') ?></span></div></div>
      </div>
    </div>
  </div>

  <div class="tabs-row">
    <div class="hud">
      <h2>About this gadget</h2>
      <p style="color:var(--muted);max-width:70ch"><?= nl2br(e($p['description'])) ?></p>
      <h3 style="margin-top:1.5rem">Specifications</h3>
      <table class="spec-table">
        <tr><th>Brand</th><td><?= e($p['brand']) ?></td></tr>
        <tr><th>Category</th><td><?= e($p['category_name']) ?></td></tr>
        <?php if ($p['sku']): ?><tr><th>SKU</th><td><?= e($p['sku']) ?></td></tr><?php endif; ?>
        <tr><th>Warranty</th><td><?= e($p['warranty'] ?: '—') ?></td></tr>
        <tr><th>Return policy</th><td><?= e($p['return_policy'] ?: '—') ?></td></tr>
        <?php foreach ($specs as $k => $v): ?><tr><th><?= e($k) ?></th><td><?= e($v) ?></td></tr><?php endforeach; ?>
        <?php if ($p['api_price_usd']): ?><tr><th>Catalog price</th><td>US$<?= e(number_format((float) $p['api_price_usd'], 2)) ?> (DummyJSON), converted to PHP</td></tr><?php endif; ?>
      </table>
    </div>

    <div class="hud" id="reviews">
      <h2>Reviews</h2>
      <div style="display:flex;gap:1.5rem;align-items:center">
        <div><div class="big-rating"><?= number_format((float) $p['rating'], 1) ?></div><span class="rating"><?= stars((float) $p['rating']) ?></span></div>
        <div class="bars" style="flex:1">
          <?php foreach ($breakdown as $star => $n): ?>
            <div class="bar"><span><?= $star ?>★</span><i style="--w: <?= $total ? round($n / $total * 100) : 0 ?>%"></i><span><?= $n ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>

      <?php if ($canReview): ?>
        <form class="hud" style="margin:1rem 0;background:var(--void-2)" action="<?= e(url('/products/' . $p['id'] . '/reviews')) ?>" method="post">
          <?= csrf_field() ?>
          <b style="font-family:var(--font-display)">Rate your purchase</b>
          <div class="star-input" style="margin:.5rem 0">
            <?php for ($s = 5; $s >= 1; $s--): ?>
              <input type="radio" id="star<?= $s ?>" name="rating" value="<?= $s ?>" <?= $s === 5 ? 'checked' : '' ?>><label for="star<?= $s ?>" title="<?= $s ?> stars">★</label>
            <?php endfor; ?>
          </div>
          <div class="<?= field_class('comment') ?>">
            <label for="comment">Your review</label>
            <textarea class="input" id="comment" name="comment" required minlength="5" maxlength="1000" placeholder="How’s the battery, screen, build?"><?= e(old('comment')) ?></textarea>
            <?= field_error('comment') ?>
          </div>
          <button class="btn btn--sm" type="submit">Post review</button>
        </form>
      <?php endif; ?>

      <?php if (!$reviews): ?><p class="muted">No reviews yet. Buyers can review after delivery.</p><?php endif; ?>
      <?php foreach ($reviews as $r): ?>
        <div class="review">
          <div class="review__head"><b><?= e(mask_name($r['reviewer_name'])) ?></b><span class="rating"><?= stars((float) $r['rating']) ?></span></div>
          <p style="margin:.2rem 0"><?= e($r['comment']) ?></p>
          <span class="small dim"><?= e(date('M j, Y', strtotime($r['created_at']))) ?><?= $r['user_id'] ? ' — Verified purchase' : '' ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($related): ?>
  <section class="section">
    <div class="section__head"><h2>More <?= e(strtolower($p['category_name'])) ?></h2></div>
    <div class="grid-products">
      <?php foreach ($related as $rp) echo View::partial('partials/product-card', ['p' => $rp]); ?>
    </div>
  </section>
  <?php endif; ?>
</div>
