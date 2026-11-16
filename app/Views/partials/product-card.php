<?php
/** @var array $p */
$wishlist = $wishlist ?? [];
$me = auth();
$off = discount_pct($p);
$saved = in_array((int) $p['id'], $wishlist, true);
?>
<article class="card">
  <div class="card__media">
  <a class="card__img" href="<?= e(url('/products/' . $p['id'])) ?>" tabindex="-1" aria-hidden="true">
    <?= product_img($p['thumbnail'], $p['name']) ?>
    <?php if ($off > 0): ?><span class="card__tag">−<?= $off ?>%</span><?php endif; ?>
    <?php if ((int) $p['stock'] === 0): ?><span class="card__oos">Out of stock</span><?php endif; ?>
  </a>
  <?php if (!$me || $me['role'] === 'buyer'): ?>
    <button class="card__wish js-wish <?= $saved ? 'is-saved' : '' ?>" type="button" aria-pressed="<?= $saved ? 'true' : 'false' ?>"
            aria-label="Save <?= e($p['name']) ?> to wishlist"
            data-url="<?= e(url('/wishlist/' . $p['id'] . '/toggle')) ?>" data-login="<?= e(url('/login')) ?>"
            <?= $me ? '' : 'data-guest="' . e(url('/login')) . '"' ?>><?= icon('heart', 18) ?></button>
  <?php endif; ?>
  </div>
  <div class="card__body">
    <span class="card__brand"><?= e($p['brand']) ?></span>
    <a class="card__name" href="<?= e(url('/products/' . $p['id'])) ?>"><?= e($p['name']) ?></a>
    <div class="price-row"><span class="price"><?= e(peso($p['price'])) ?></span>
      <?php if ($off > 0): ?><s class="was"><?= e(peso($p['compare_price'])) ?></s><?php endif; ?></div>
    <div class="card__meta">
      <span><span class="rating" aria-label="Rated <?= e($p['rating']) ?> of 5">★</span> <?= number_format((float) $p['rating'], 1) ?> (<?= (int) $p['rating_count'] ?>)</span>
      <span><?= (int) $p['sold_count'] ?> sold</span>
    </div>
  </div>
</article>
