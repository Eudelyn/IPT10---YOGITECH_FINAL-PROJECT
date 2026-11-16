<div class="page-head">
  <div><h1>Products</h1><p><?= (int) $pager->total ?> product(s)<?= $q ? ' matching “' . e($q) . '”' : '' ?></p></div>
</div>
<div class="toolbar">
  <nav class="tabs" style="margin:0;border:0">
    <?php foreach (['' => 'All', 'active' => 'Live', 'draft' => 'Drafts', 'low' => 'Low stock'] as $k => $label): ?>
      <a class="<?= $filter === $k ? 'is-active' : '' ?>" href="<?= e(url('/seller/products' . ($k ? '?filter=' . $k : ''))) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <form class="inline-form" method="get" action="<?= e(url('/seller/products')) ?>">
    <label class="sr-only" for="pq">Search products</label>
    <input class="input" id="pq" name="q" value="<?= e($q) ?>" placeholder="Name, SKU or brand">
    <button class="btn btn--quiet btn--sm" type="submit"><?= icon('search', 16) ?></button>
  </form>
</div>
<div class="hud hud--flush">
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th></th><th>Product</th><th>Category</th><th class="num">Price</th><th class="num">Stock</th><th class="num">Sold</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php if (!$products): ?><tr><td colspan="8"><div class="empty"><h3>No products found</h3><p>Add one manually or import ready-made products.</p><a class="btn btn--sm" href="<?= e(url('/seller/import')) ?>">Import products</a></div></td></tr><?php endif; ?>
      <?php foreach ($products as $p): ?>
        <tr>
          <td><?= product_img($p['thumbnail'], '', 'class="thumb"') ?></td>
          <td><a href="<?= e(url('/seller/products/' . $p['id'] . '/edit')) ?>"><b><?= e($p['name']) ?></b></a><br>
            <span class="small dim"><?= e($p['brand']) ?><?= $p['sku'] ? ', ' . e($p['sku']) : '' ?></span>
            <?php if ($p['source'] === 'dummyjson'): ?> <span class="pill pill--api">API</span><?php endif; ?></td>
          <td><?= e($p['category_name']) ?></td>
          <td class="num"><?= e(peso($p['price'])) ?><?php if ($p['compare_price']): ?><br><s class="small dim"><?= e(peso($p['compare_price'])) ?></s><?php endif; ?></td>
          <td class="num"><?php if ((int) $p['stock'] <= (int) config('app.low_stock')): ?><span class="pill pill--low"><?= (int) $p['stock'] ?></span><?php else: ?><?= (int) $p['stock'] ?><?php endif; ?></td>
          <td class="num"><?= (int) $p['sold_count'] ?></td>
          <td><?= $p['status'] === 'active' ? '<span class="status status--delivered">Live</span>' : '<span class="pill pill--draft">Draft</span>' ?></td>
          <td style="white-space:nowrap">
            <a class="icon-btn" href="<?= e(url('/products/' . $p['id'])) ?>" title="View in store" aria-label="View <?= e($p['name']) ?>"><?= icon('store', 18) ?></a>
            <a class="icon-btn" href="<?= e(url('/seller/products/' . $p['id'] . '/edit')) ?>" title="Edit" aria-label="Edit <?= e($p['name']) ?>"><?= icon('edit', 18) ?></a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= \Core\View::partial('partials/pagination', ['pager' => $pager]) ?>
</div>
