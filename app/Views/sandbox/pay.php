<?php
$isGcash = $payment['method'] === 'gcash';
$err = static fn(string $k) => errors($k) ? '<span class="err">' . e(errors($k)) . '</span>' : '';
$cls = static fn(string $k) => errors($k) ? 'has-error' : '';
$base = '/sandbox-pay/' . $payment['public_id'];
$phone = preg_replace('/\D/', '', (string) ($buyer['phone'] ?? ''));
?>
<div class="gw <?= $isGcash ? 'gw--gcash' : 'gw--card' ?>">
  <div class="gw__head">
    <span class="gw__brand"><?= $isGcash ? 'GCash' : '🔒 Secure card payment' ?></span>
    <span class="pill">SANDBOX</span>
  </div>
  <div class="gw__merchant">
    <img src="<?= e(asset('assets/img/logo.svg')) ?>" alt="">
    <div><b>Yogitech</b><small>Order ref <?= e($payment['checkout_ref']) ?></small></div>
    <div class="gw__amount"><small>PHP</small> <?= e(number_format((float) $payment['amount'], 2)) ?></div>
  </div>
  <div class="gw__body">
    <?php if ($msg = flash('success')): ?><div class="flash"><?= e($msg) ?></div><?php endif; ?>

    <?php if ($step === 'login'): ?>
      <h1>Log in to pay</h1>
      <p>Enter your GCash number and 4-digit MPIN.</p>
      <form method="post" action="<?= e(url($base . '/login')) ?>" data-once>
        <?= csrf_field() ?>
        <div class="<?= $cls('number') ?>"><label for="number">GCash number</label><input id="number" name="number" inputmode="tel" autocomplete="tel" placeholder="09XXXXXXXXX" value="<?= e(old('number', $phone)) ?>" required><?= $err('number') ?></div>
        <div class="<?= $cls('mpin') ?>"><label for="mpin">MPIN</label><input id="mpin" name="mpin" type="password" inputmode="numeric" maxlength="4" autocomplete="off" placeholder="••••" required autofocus><?= $err('mpin') ?></div>
        <button class="btn" type="submit">Next</button>
      </form>

    <?php elseif ($step === 'otp' || $step === 'card_otp'): ?>
      <div class="sms"><small>📱 Sandbox SMS · <?= $step === 'otp' ? 'to ' . e(\App\Controllers\SandboxPayController::maskNumber((string) $st['number'])) : 'from your bank' ?></small>
        <?= $step === 'otp' ? 'GCash' : 'Bank' ?>: Your authentication code is <b><?= e($st['otp']) ?></b>. Never share this code.</div>
      <h1><?= $step === 'otp' ? 'Enter the code' : '3-D Secure verification' ?></h1>
      <p><?= $step === 'otp' ? 'We sent a 6-digit code by SMS.' : 'Your bank sent a one-time code to confirm ' . e($st['card']) . '.' ?></p>
      <form method="post" action="<?= e(url($base . '/otp')) ?>" data-once>
        <?= csrf_field() ?>
        <div class="<?= $cls('code') ?>"><label for="code">One-time code</label><input class="code" id="code" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="••••••" required autofocus><?= $err('code') ?></div>
        <button class="btn" type="submit">Verify</button>
      </form>
      <form method="post" action="<?= e(url($base . '/resend')) ?>"><?= csrf_field() ?><button class="link" type="submit">Resend code</button></form>

    <?php elseif ($step === 'review'): ?>
      <h1>Review and pay</h1>
      <div class="kv"><span>Pay to</span><b>Yogitech</b></div>
      <div class="kv"><span>From</span><b>GCash <?= e(\App\Controllers\SandboxPayController::maskNumber((string) $st['number'])) ?></b></div>
      <div class="kv"><span>Available balance</span><b>PHP <?= e(number_format($balance, 2)) ?></b></div>
      <div class="kv"><span>Amount</span><b>PHP <?= e(number_format((float) $payment['amount'], 2)) ?></b></div>
      <?php if ((float) $payment['amount'] > $balance): ?><p class="notice" style="margin-top:1rem">Your balance is not enough for this payment.</p><?php endif; ?>
      <form method="post" action="<?= e(url($base . '/approve')) ?>" data-once>
        <?= csrf_field() ?>
        <button class="btn" type="submit">Pay PHP <?= e(number_format((float) $payment['amount'], 2)) ?></button>
      </form>

    <?php else: /* card */ ?>
      <h1>Pay with card</h1>
      <p>Visa, Mastercard or JCB. Your bank will ask for a one-time code.</p>
      <form method="post" action="<?= e(url($base . '/card')) ?>" data-once autocomplete="off">
        <?= csrf_field() ?>
        <div class="<?= $cls('card_name') ?>"><label for="card_name">Name on card</label><input id="card_name" name="card_name" autocomplete="cc-name" value="<?= e(old('card_name', $buyer['name'] ?? '')) ?>" required><?= $err('card_name') ?></div>
        <div class="<?= $cls('card_number') ?>"><label for="card_number">Card number</label><input id="card_number" name="card_number" inputmode="numeric" autocomplete="cc-number" placeholder="4242 4242 4242 4242" required><?= $err('card_number') ?></div>
        <div class="row">
          <div class="<?= $cls('card_expiry') ?>"><label for="card_expiry">Expiry</label><input id="card_expiry" name="card_expiry" inputmode="numeric" autocomplete="cc-exp" placeholder="MM/YY" required><?= $err('card_expiry') ?></div>
          <div class="<?= $cls('card_cvc') ?>"><label for="card_cvc">CVC</label><input id="card_cvc" name="card_cvc" inputmode="numeric" maxlength="4" autocomplete="cc-csc" placeholder="123" required><?= $err('card_cvc') ?></div>
        </div>
        <button class="btn" type="submit">Continue</button>
      </form>
    <?php endif; ?>

    <form method="post" action="<?= e(url($base . '/cancel')) ?>"><?= csrf_field() ?><button class="link" type="submit">Cancel and go back to Yogitech</button></form>

    <div class="tests">
      <b>Sandbox test data</b><br>
      <?php if ($isGcash): ?>
        Any 4-digit MPIN works, <code>0000</code> = wrong MPIN (3 tries). The SMS code is shown on the next step.
        Wallet balance is PHP <?= e(number_format($balance, 2)) ?>; bigger orders fail with “insufficient balance”.
      <?php else: ?>
        Success: <code>4242 4242 4242 4242</code> · Declined: <code>4000 0000 0000 0002</code> · No funds: <code>4000 0000 0000 9995</code>.
        Any future expiry and any 3-digit CVC.
      <?php endif; ?>
    </div>
  </div>
</div>
