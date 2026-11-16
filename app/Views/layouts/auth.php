<!doctype html>
<html lang="en">
<head><?= \Core\View::partial('partials/head', ['title' => $title ?? null]) ?></head>
<body>
<?= \Core\View::partial('partials/flash') ?>
<div class="auth">
  <aside class="auth__art">
    <a class="logo" href="<?= e(url('/')) ?>"><img class="logo__mark" src="<?= e(asset('assets/img/logo.svg')) ?>" alt="" width="40" height="40"><span>YOGI<span class="y">TECH</span></span></a>
    <div>
      <p class="wordmark" data-glitch aria-hidden="true">PLUG<span class="row2">IN.</span></p>
      <p>Phones, laptops, tablets and accessories. Pay in pesos, track every parcel.</p>
    </div>
    <span class="small dim">Cash on delivery, GCash and card. Free shipping on <?= e(peso(config('shipping.free_threshold'), false)) ?>+.</span>
  </aside>
  <main class="auth__form" id="main"><div class="auth__box"><?= $content ?></div></main>
</div>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
