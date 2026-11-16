<?php
use App\Models\Product;
use Core\View;
/** @var Core\Paginator $pager */
$q = $filters['q'] ?? '';
$qs = static function (array $over) use ($filters) {
    $base = array_filter(array_merge($filters, $over), static fn($v) => $v !== null && $v !== '');
    unset($base['seller'], $base['store']);
    return '?' . http_build_query($base);
};
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/products', PHP_URL_PATH);
$onProducts = substr(rtrim((string) $path, '/'), -9) === '/products';
?>
<div class="wrap">
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="<?= e(url('/')) ?>">Home</a><span>/</span>
    <?php if ($store): ?><span><?= e($store['store_name'] ?: $store['name']) ?></span>
    <?php else: ?><a href="<?= e(url('/products')) ?>">Gadgets</a><?php if ($category): ?><span>/</span><span><?= e($category['name']) ?></span><?php endif; ?><?php endif; ?>
  </nav>

  <?php if ($store): ?>
    <div class="hud" style="margin-bottom:1.5rem;display:flex;gap:1rem;align-items:center">
      <?= icon('store', 40) ?>
      <div><h1 style="margin:0"><?= e($store['store_name'] ?: $store['name']) ?></h1>
        <p class="muted" style="margin:.2rem 0 0"><?= e($store['store_description'] ?: 'Seller on Yogitech') ?><?= $store['city'] ? ' — ships from ' . e($store['city']) : '' ?></p></div>
    </div>
  <?php else: ?>
    <div class="page-head" style="margin-top:0">
      <div><h1><?= e($title) ?></h1><p><?= e($category['description'] ?? 'Every gadget from every Yogitech seller, in one place.') ?></p></div>
    </div>
  <?php endif; ?>

  <div class="catalog">
    <aside class="hud filters" aria-label="Filters">
      <form method="get" action="<?= e($path) ?>">
        <?php if ($q): ?><input type="hidden" name="q" value="<?= e($q) ?>"><?php endif; ?>
        <?php if (!empty($filters['sort'])): ?><input type="hidden" name="sort" value="<?= e($filters['sort']) ?>"><?php endif; ?>
        <?php if ($onProducts && !empty($filters['category'])): ?><input type="hidden" name="category" value="<?= e($filters['category']) ?>"><?php endif; ?>

        <?php if (!$store): ?>
        <h3>Category</h3>
        <a class="filter-link <?= empty($filters['category']) ? 'is-active' : '' ?>" href="<?= e(url('/products' . $qs(['category' => null, 'brand' => null, 'page' => null]))) ?>">All<span></span></a>
        <?php foreach ($categories as $c): ?>
          <a class="filter-link <?= ($filters['category'] ?? '') === $c['slug'] ? 'is-active' : '' ?>"
             href="<?= e(url('/category/' . $c['slug'] . $qs(['category' => null, 'brand' => null, 'page' => null]))) ?>"><?= e($c['name']) ?><span><?= (int) $c['product_count'] ?></span></a>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($brands): ?>
          <h3>Brand</h3>
          <select class="input" name="brand" data-autosubmit aria-label="Brand">
            <option value="">All brands</option>
            <?php foreach ($brands as $b): ?><option value="<?= e($b['brand']) ?>" <?= ($filters['brand'] ?? '') === $b['brand'] ? 'selected' : '' ?>><?= e($b['brand']) ?> (<?= (int) $b['n'] ?>)</option><?php endforeach; ?>
          </select>
        <?php endif; ?>

        <h3>Price (₱)</h3>
        <div class="grid-2" style="gap:.5rem">
          <input class="input" type="number" name="min" min="0" placeholder="Min" value="<?= e($filters['min'] ?? '') ?>" aria-label="Minimum price">
          <input class="input" type="number" name="max" min="0" placeholder="Max" value="<?= e($filters['max'] ?? '') ?>" aria-label="Maximum price">
        </div>

        <h3>Rating</h3>
        <select class="input" name="rating" aria-label="Minimum rating">
          <option value="">Any rating</option>
          <?php foreach ([4, 3] as $r): ?><option value="<?= $r ?>" <?= (string) ($filters['rating'] ?? '') === (string) $r ? 'selected' : '' ?>><?= $r ?>★ and up</option><?php endforeach; ?>
        </select>

        <label class="check" style="margin:1rem 0"><input type="checkbox" name="in_stock" value="1" <?= !empty($filters['in_stock']) ? 'checked' : '' ?>> In stock only</label>
        <button class="btn btn--cyan btn--block btn--sm" type="submit">Apply filters</button>
        <a class="btn btn--quiet btn--block btn--sm" style="margin-top:.5rem" href="<?= e($path) ?>">Clear</a>
      </form>
    </aside>

    <section>
      <div class="toolbar">
        <span class="muted"><?= $pager->total ? 'Showing ' . $pager->from() . '–' . $pager->to() . ' of ' . $pager->total : 'No results' ?><?= $q ? ' for “' . e($q) . '”' : '' ?></span>
        <form method="get" action="<?= e($path) ?>">
          <?php foreach ($filters as $k => $v): if ($k !== 'sort' && $k !== 'seller' && $k !== 'store' && $v !== null && $v !== '' && !($k === 'category' && !$onProducts)): ?>
            <input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>">
          <?php endif; endforeach; ?>
          <label class="sr-only" for="sort">Sort by</label>
          <select class="input" id="sort" name="sort" data-autosubmit>
            <?php foreach (Product::SORTS as $key => [$label]): ?><option value="<?= e($key) ?>" <?= $filters['sort'] === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
          </select>
        </form>
      </div>

      <?php if ($pager->items): ?>
        <div class="grid-products">
          <?php foreach ($pager->items as $p) echo View::partial('partials/product-card', ['p' => $p, 'wishlist' => $wishlist]); ?>
        </div>
        <?= View::partial('partials/pagination', ['pager' => $pager]) ?>
      <?php else: ?>
        <div class="hud empty">
          <?= icon('search', 48) ?>
          <h3>No gadgets match these filters</h3>
          <p>Try a broader search, remove a filter, or check the spelling of the brand.</p>
          <a class="btn" href="<?= e(url('/products')) ?>">Show all gadgets</a>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>
