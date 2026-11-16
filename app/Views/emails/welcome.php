<h1 style="margin:0 0 10px;font-size:24px;color:#fcee0a">Welcome, <?= e(explode(' ', $name)[0]) ?></h1>
<p style="margin:0 0 14px;font-size:15px;line-height:1.5"><?= $seller
  ? 'Your Yogitech store is ready. Add products or import a catalog, then you’ll get an email here whenever a buyer orders.'
  : 'Your account is ready. We’ll email you when your orders are confirmed, shipped and delivered.' ?></p>
<p style="margin:22px 0 0"><a href="<?= e($cta) ?>" style="display:inline-block;background:#fcee0a;color:#0c0a17;text-decoration:none;font-weight:bold;padding:12px 20px"><?= $seller ? 'Open seller center' : 'Start shopping' ?></a></p>
