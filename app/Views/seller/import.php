<div class="page-head">
  <div><h1>Import products</h1><p>Add ready-made gadget listings to your store in one click. Prices are converted from US dollars to pesos using today’s exchange rate.</p></div>
</div>
<div class="dash-grid">
  <section class="hud">
    <h2>Choose a category</h2>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>Category</th><th>Source</th><th class="num">Already imported</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($categories as $c): ?>
          <tr>
            <td><b style="display:inline-flex;gap:.5rem;align-items:center"><?= icon($c['icon'], 18) ?><?= e($c['name']) ?></b></td>
            <td class="small muted"><code>GET /products/category/<?= e($c['api_slug']) ?></code></td>
            <td class="num"><?= (int) ($counts[$c['id']] ?? 0) ?></td>
            <td style="text-align:right">
              <form action="<?= e(url('/seller/import')) ?>" method="post">
                <?= csrf_field() ?><input type="hidden" name="category_id" value="<?= (int) $c['id'] ?>">
                <button class="btn btn--sm <?= !empty($counts[$c['id']]) ? 'btn--ghost' : '' ?>" type="submit"><?= icon('download', 16) ?><?= !empty($counts[$c['id']]) ? 'Refresh prices' : 'Import' ?></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="hint" style="margin-top:1rem">Re-importing updates price and images on products you already imported. Your stock, description and status edits are kept.</p>
  </section>
  <section class="hud">
    <h3>Exchange rate</h3>
    <p class="price" style="font-size:1.8rem;margin:.2rem 0">US$1 = <?= e(peso($fx['rate'])) ?></p>
    <p class="small muted">Source: <?= e($fx['provider']) ?> (<?= $fx['source'] === 'live' ? 'live, cached 12 h' : 'fallback, API unreachable' ?>)<br>Updated <?= e($fx['updated_at']) ?></p>
    <h3 style="margin-top:1.2rem">How prices are set</h3>
    <p class="small muted">Original price = USD × rate, rounded to end in 9. Sale price applies the API’s discount percentage. Example: US$1,099.99 at ₱<?= e(number_format($fx['rate'], 2)) ?> becomes <?= e(peso(\App\Services\ExchangeRateService::toPeso(1099.99, (float) $fx['rate']), false)) ?>.</p>
  </section>
</div>
