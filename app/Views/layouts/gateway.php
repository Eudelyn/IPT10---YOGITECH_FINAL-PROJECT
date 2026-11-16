<?php /** Stand-alone layout for the sandbox payment gateway: looks like a separate payment site. */ ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title ?? 'Payment') ?> · Sandbox</title>
<style>
  *{box-sizing:border-box} body{margin:0;font:15px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#eef2f8;color:#14213d;min-height:100vh;display:flex;flex-direction:column;align-items:center}
  .sbx-bar{width:100%;background:#14213d;color:#fff;font-size:.8rem;text-align:center;padding:.45rem 1rem;letter-spacing:.02em}
  .sbx-bar b{color:#ffd60a}
  .gw{width:min(420px,calc(100% - 2rem));margin:1.5rem 0 2rem;background:#fff;border-radius:16px;box-shadow:0 10px 40px rgba(20,33,61,.15);overflow:hidden}
  .gw__head{padding:1.1rem 1.4rem;color:#fff;display:flex;align-items:center;justify-content:space-between}
  .gw--gcash .gw__head{background:linear-gradient(135deg,#0a5bd3,#2e8bff)} .gw--card .gw__head{background:linear-gradient(135deg,#1d2b4f,#3a4f86)}
  .gw__brand{font-weight:800;font-size:1.35rem;letter-spacing:.01em} .pill{font-size:.7rem;font-weight:700;background:rgba(255,255,255,.2);padding:.15rem .55rem;border-radius:99px}
  .gw__merchant{display:flex;align-items:center;gap:.7rem;padding:1rem 1.4rem;border-bottom:1px solid #e6ebf3}
  .gw__merchant img{width:40px;height:40px} .gw__merchant small{display:block;color:#6b7896;font-size:.8rem}
  .gw__amount{margin-left:auto;text-align:right;font-weight:800;font-size:1.25rem} .gw__amount small{font-weight:500}
  .gw__body{padding:1.3rem 1.4rem}
  h1{font-size:1.1rem;margin:0 0 .3rem} p{margin:.2rem 0 1rem;color:#4b5875}
  label{display:block;font-weight:600;font-size:.85rem;margin:.8rem 0 .3rem}
  input{width:100%;padding:.75rem .9rem;border:1.5px solid #ccd5e4;border-radius:10px;font:inherit;font-size:1rem;outline:none}
  input:focus{border-color:#2e8bff;box-shadow:0 0 0 3px rgba(46,139,255,.15)} .has-error input{border-color:#e5383b}
  .err{color:#e5383b;font-size:.82rem;display:block;margin-top:.3rem}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:.8rem;align-items:start}
  .btn{display:block;width:100%;margin-top:1.2rem;padding:.85rem;border:0;border-radius:99px;font:700 1rem system-ui,sans-serif;cursor:pointer;color:#fff;background:#0a5bd3}
  .gw--card .btn{background:#1d2b4f} .btn:disabled{opacity:.6}
  .link{background:none;border:0;color:#6b7896;font:inherit;cursor:pointer;text-decoration:underline;padding:.6rem 0 0;display:block;margin:0 auto}
  .sms{background:#14213d;color:#fff;border-radius:14px;padding:.8rem 1rem;margin-bottom:1rem;font-size:.9rem;box-shadow:0 6px 20px rgba(20,33,61,.25)}
  .sms small{color:#9fb0d3;display:block;font-size:.75rem} .sms b{color:#ffd60a;letter-spacing:.15em;font-size:1.05rem}
  .code{letter-spacing:.5em;text-align:center;font-size:1.4rem;font-weight:700}
  .kv{display:flex;justify-content:space-between;padding:.45rem 0;border-bottom:1px dashed #e6ebf3} .kv span{color:#6b7896}
  .notice{background:#fff4d6;border:1px solid #ffd60a;border-radius:10px;padding:.6rem .8rem;font-size:.85rem;color:#5c4400;margin-bottom:.8rem}
  .flash{background:#e7f6ec;border:1px solid #9ad7ae;border-radius:10px;padding:.6rem .8rem;font-size:.85rem;margin-bottom:.8rem}
  .tests{margin-top:1.2rem;background:#f4f6fb;border-radius:10px;padding:.7rem .9rem;font-size:.8rem;color:#4b5875}
  .tests b{color:#14213d} .tests code{background:#fff;padding:.05rem .3rem;border-radius:4px}
  footer{font-size:.75rem;color:#6b7896;margin-bottom:1.5rem;text-align:center}
</style>
</head>
<body>
  <div class="sbx-bar"><b>SANDBOX</b> payment gateway · no real money moves · built into Yogitech for testing</div>
  <?= $content ?>
  <footer>Secured by Yogitech Sandbox Pay · <?= e(date('Y')) ?></footer>
  <script>
    document.querySelectorAll('form[data-once]').forEach(function (f) {
      f.addEventListener('submit', function () { var b = f.querySelector('button[type=submit]'); if (b) { setTimeout(function () { b.disabled = true; }, 0); } });
    });
    var cn = document.getElementById('card_number');
    if (cn) cn.addEventListener('input', function () { var d = cn.value.replace(/\D/g, '').slice(0, 19); cn.value = d.replace(/(.{4})/g, '$1 ').trim(); });
    var ex = document.getElementById('card_expiry');
    if (ex) ex.addEventListener('input', function () { var d = ex.value.replace(/\D/g, '').slice(0, 4); ex.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d; });
  </script>
</body>
</html>
