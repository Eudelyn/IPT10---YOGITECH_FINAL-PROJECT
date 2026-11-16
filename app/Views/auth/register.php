<?php $r = old('role', $role); ?>
<h1>Create your account</h1>
<p class="muted">Already have one? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
<form action="<?= e(url('/register')) ?>" method="post" novalidate data-validate>
  <?= csrf_field() ?>
<?php if (!empty($sellerSignup)): ?>
  <div class="role-switch" role="radiogroup" aria-label="Account type">
    <label><input type="radio" name="role" value="buyer" <?= $r !== 'seller' ? 'checked' : '' ?> onchange="const f=document.getElementById('store-field'); f.hidden=true; f.querySelector('input').disabled=true"><b><?= icon('cart', 18) ?>I want to shop</b><span>Buy gadgets, track orders</span></label>
    <label><input type="radio" name="role" value="seller" <?= $r === 'seller' ? 'checked' : '' ?> onchange="const f=document.getElementById('store-field'); f.hidden=false; f.querySelector('input').disabled=false"><b><?= icon('store', 18) ?>I want to sell</b><span>Open a store, ship orders</span></label>
  </div>
<?php endif; ?>
  <div class="<?= field_class('name') ?>">
    <label for="name">Full name</label>
    <input class="input" id="name" name="name" autocomplete="name" required value="<?= e(old('name')) ?>">
    <?= field_error('name') ?>
  </div>
<?php if (!empty($sellerSignup)): ?>
  <div class="<?= field_class('store_name') ?>" id="store-field" <?= $r === 'seller' ? '' : 'hidden' ?>>
    <label for="store_name">Store name</label>
    <input class="input" id="store_name" name="store_name" <?= $r === 'seller' ? '' : 'disabled' ?> value="<?= e(old('store_name')) ?>" placeholder="e.g. Pampanga Phone Hub">
    <?= field_error('store_name') ?>
  </div>
<?php endif; ?>
  <div class="<?= field_class('email') ?>">
    <label for="email">Email</label>
    <input class="input" id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>">
    <?= field_error('email') ?>
  </div>
  <div class="<?= field_class('phone') ?>">
    <label for="phone">Mobile number <span class="dim">(optional)</span></label>
    <input class="input" id="phone" name="phone" inputmode="tel" placeholder="09XXXXXXXXX" value="<?= e(old('phone')) ?>">
    <?= field_error('phone') ?>
  </div>
  <div class="grid-2">
    <div class="<?= field_class('password') ?>">
      <label for="password">Password</label>
      <input class="input" id="password" name="password" type="password" autocomplete="new-password" required minlength="8">
      <?= field_error('password') ?>
    </div>
    <div class="<?= field_class('password_confirmation') ?>">
      <label for="password_confirmation">Confirm password</label>
      <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
    </div>
  </div>
  <p class="hint" style="margin-top:-.5rem">At least 8 characters with a letter and a number.</p>
  <div class="<?= field_class('terms') ?>">
    <label class="check"><input type="checkbox" name="terms" value="1" required data-label="Agreement to the terms"> I agree to the Yogitech terms and privacy policy.</label>
    <?= field_error('terms') ?>
  </div>
  <button class="btn btn--block" type="submit">Create account</button>
</form>
