<?php
$p = $product ?? [];
$val = static fn(string $k, $d = '') => old($k, $p[$k] ?? $d);
$imgs = implode("\n", json_decode((string) ($p['images'] ?? '[]'), true) ?: []);
$action = $product ? url('/seller/products/' . $p['id']) : url('/seller/products');
?>
<div class="page-head">
  <div><h1><?= $product ? 'Edit product' : 'Add product' ?></h1><p><?= $product ? e($p['name']) : 'Prices are in Philippine pesos.' ?></p></div>
  <?php if ($product): ?><a class="btn btn--ghost" href="<?= e(url('/products/' . $p['id'])) ?>"><?= icon('store', 18) ?>View in store</a><?php endif; ?>
</div>
<form action="<?= e($action) ?>" method="post" enctype="multipart/form-data" class="dash-grid" novalidate>
  <?= csrf_field() ?>
  <div>
    <section class="hud">
      <h2>Details</h2>
      <div class="<?= field_class('name') ?>"><label for="name">Product name</label><input class="input" id="name" name="name" required value="<?= e($val('name')) ?>" placeholder="e.g. Galaxy S24 Ultra 256GB"><?= field_error('name') ?></div>
      <div class="grid-3">
        <div class="<?= field_class('category_id') ?>"><label for="category_id">Category</label>
          <select class="input" id="category_id" name="category_id"><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (string) $val('category_id') === (string) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><?= field_error('category_id') ?></div>
        <div class="<?= field_class('brand') ?>"><label for="brand">Brand</label><input class="input" id="brand" name="brand" value="<?= e($val('brand')) ?>"><?= field_error('brand') ?></div>
        <div class="<?= field_class('sku') ?>"><label for="sku">SKU</label><input class="input" id="sku" name="sku" value="<?= e($val('sku')) ?>"><?= field_error('sku') ?></div>
      </div>
      <div class="<?= field_class('description') ?>"><label for="description">Description</label><textarea class="input" id="description" name="description" rows="6" required><?= e($val('description')) ?></textarea><?= field_error('description') ?></div>
      <div class="grid-2">
        <div class="<?= field_class('warranty') ?>"><label for="warranty">Warranty</label><input class="input" id="warranty" name="warranty" value="<?= e($val('warranty')) ?>" placeholder="1 year local warranty"><?= field_error('warranty') ?></div>
        <div class="<?= field_class('return_policy') ?>"><label for="return_policy">Return policy</label><input class="input" id="return_policy" name="return_policy" value="<?= e($val('return_policy')) ?>" placeholder="7 days return policy"><?= field_error('return_policy') ?></div>
      </div>
    </section>
    <section class="hud">
      <h2>Images</h2>
      <div class="grid-2">
        <div class="<?= field_class('thumbnail_file') ?>"><label for="thumbnail_file">Upload main image</label><input class="input" id="thumbnail_file" name="thumbnail_file" type="file" accept="image/jpeg,image/png,image/webp,image/gif"><span class="hint">JPG, PNG, WebP or GIF up to 2 MB</span><?= field_error('thumbnail_file') ?></div>
        <div class="<?= field_class('thumbnail_url') ?>"><label for="thumbnail_url">…or main image URL</label><input class="input" id="thumbnail_url" name="thumbnail_url" type="url" value="<?= e(old('thumbnail_url', preg_match('#^https?://#', (string) ($p['thumbnail'] ?? '')) ? $p['thumbnail'] : '')) ?>" placeholder="https://"><?= field_error('thumbnail_url') ?></div>
      </div>
      <div class="<?= field_class('image_urls') ?>"><label for="image_urls">Gallery image URLs (one per line)</label><textarea class="input" id="image_urls" name="image_urls" rows="3"><?= e(old('image_urls', $imgs)) ?></textarea><?= field_error('image_urls') ?></div>
    </section>
    <?php $curVideo = $p['video_url'] ?? null; $cv = product_video($curVideo); $maxMb = round(\App\Controllers\Seller\ProductController::maxVideoBytes() / 1048576); ?>
    <section class="hud">
      <h2>Video <span class="dim small">(optional)</span></h2>
      <p class="small muted" style="margin-top:-.4rem">Shown first on the product page, before the photos. Good for a quick demo or how-to.</p>
      <?php if ($cv): ?>
        <p class="small">Current video: <a href="<?= e($cv['watch']) ?>" target="_blank" rel="noopener"><?= $cv['type'] === 'youtube' ? 'YouTube video' : 'uploaded video' ?></a></p>
      <?php endif; ?>
      <div class="grid-2">
        <div class="<?= field_class('video_url') ?>"><label for="video_url">YouTube link</label><input class="input" id="video_url" name="video_url" type="url" value="<?= e(old('video_url', preg_match('#^https?://#', (string) $curVideo) ? $curVideo : '')) ?>" placeholder="https://www.youtube.com/watch?v=…"><?= field_error('video_url') ?></div>
        <div class="<?= field_class('video_file') ?>"><label for="video_file">…or upload a video</label><input class="input" id="video_file" name="video_file" type="file" accept="video/mp4,video/webm"><span class="hint">MP4 or WebM up to <?= (int) $maxMb ?> MB</span><?= field_error('video_file') ?></div>
      </div>
      <?php if ($cv): ?><label class="check"><input type="checkbox" name="remove_video" value="1"> Remove the video</label><?php endif; ?>
    </section>
  </div>
  <div>
    <section class="hud">
      <div style="aspect-ratio:1;background:var(--void-2);display:grid;place-items:center;margin-bottom:1rem">
        <?= product_img($p['thumbnail'] ?? null, 'Preview', 'data-thumb-preview style="max-height:100%;object-fit:contain;padding:1rem"') ?>
      </div>
      <div class="grid-2">
        <div class="<?= field_class('price') ?>"><label for="price">Price (₱)</label><input class="input" id="price" name="price" type="number" step="0.01" min="1" required value="<?= e($val('price')) ?>"><?= field_error('price') ?></div>
        <div class="<?= field_class('compare_price') ?>"><label for="compare_price">Original price (₱)</label><input class="input" id="compare_price" name="compare_price" type="number" step="0.01" min="0" value="<?= e($val('compare_price')) ?>"><?= field_error('compare_price') ?></div>
      </div>
      <p class="hint" style="margin-top:-.5rem">Set an original price higher than the price to show a discount.</p>
      <div class="<?= field_class('stock') ?>"><label for="stock">Stock</label><input class="input" id="stock" name="stock" type="number" min="0" required value="<?= e($val('stock', 10)) ?>"><?= field_error('stock') ?></div>
      <div class="field"><label for="status">Visibility</label>
        <select class="input" id="status" name="status"><option value="active" <?= $val('status', 'active') === 'active' ? 'selected' : '' ?>>Live in store</option><option value="draft" <?= $val('status') === 'draft' ? 'selected' : '' ?>>Draft (hidden)</option></select></div>
      <?php if (!empty($p['api_price_usd'])): ?><p class="hint">Imported from DummyJSON at US$<?= e(number_format((float) $p['api_price_usd'], 2)) ?>.</p><?php endif; ?>
      <button class="btn btn--block" type="submit"><?= $product ? 'Save changes' : 'Add product' ?></button>
    </section>
  </div>
</form>
<?php if ($product): ?>
  <form class="hud" style="margin-top:1.25rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap" action="<?= e(url('/seller/products/' . $p['id'] . '/delete')) ?>" method="post" data-confirm="Delete this product? This can't be undone.">
    <?= csrf_field() ?>
    <div><b style="font-family:var(--font-display)">Delete product</b><p class="small muted" style="margin:0">Removes it from the store and from buyers’ carts. Past orders keep their details.</p></div>
    <button class="btn btn--danger" type="submit"><?= icon('trash', 18) ?>Delete</button>
  </form>
<?php endif; ?>
