<?php $u = $user; $seller = $u['role'] === 'seller'; ?>
<?php if (!$seller): ?><div class="wrap"><?php endif; ?>
<div class="page-head">
  <div><h1><?= $seller ? 'Store settings' : 'Account settings' ?></h1><p><?= $seller ? 'Your store details appear on product pages and shipping updates.' : 'Your saved address fills in checkout automatically.' ?></p></div>
</div>
<?php if (!$seller): ?><?= \Core\View::partial('partials/account-nav') ?><br><?php endif; ?>
<div class="dash-grid">
  <form class="hud" action="<?= e(url('/account')) ?>" method="post" novalidate>
    <?= csrf_field() ?>
    <h2>Profile</h2>
    <div class="grid-2">
      <div class="<?= field_class('name') ?>"><label for="name">Full name</label><input class="input" id="name" name="name" required value="<?= e(old('name', $u['name'])) ?>"><?= field_error('name') ?></div>
      <div class="<?= field_class('email') ?>"><label for="email">Email</label><input class="input" id="email" name="email" type="email" required value="<?= e(old('email', $u['email'])) ?>"><?= field_error('email') ?></div>
      <div class="<?= field_class('email_password') ?>"><label for="email_password">Current password</label><input class="input" id="email_password" name="email_password" type="password" autocomplete="current-password"><span class="hint">Only needed when you change your email.</span><?= field_error('email_password') ?></div>
    </div>
    <div class="<?= field_class('phone') ?>"><label for="phone">Mobile number</label><input class="input" id="phone" name="phone" placeholder="09XXXXXXXXX" value="<?= e(old('phone', $u['phone'])) ?>"><?= field_error('phone') ?></div>
    <?php if ($seller): ?>
      <div class="<?= field_class('store_name') ?>"><label for="store_name">Store name</label><input class="input" id="store_name" name="store_name" required value="<?= e(old('store_name', $u['store_name'])) ?>"><?= field_error('store_name') ?></div>
      <div class="<?= field_class('store_description') ?>"><label for="store_description">Store description</label><textarea class="input" id="store_description" name="store_description" maxlength="500"><?= e(old('store_description', $u['store_description'])) ?></textarea><?= field_error('store_description') ?></div>
    <?php endif; ?>

    <h2 style="margin-top:1.5rem"><?= $seller ? 'Warehouse address' : 'Delivery address' ?></h2>
    <div class="<?= field_class('address_line') ?>"><label for="address_line">Street address</label><input class="input" id="address_line" name="address_line" placeholder="House no., street, barangay" value="<?= e(old('address_line', $u['address_line'])) ?>"><?= field_error('address_line') ?></div>
    <div class="grid-2">
      <div class="<?= field_class('city') ?>"><label for="city">City / municipality</label><input class="input" id="city" name="city" value="<?= e(old('city', $u['city'])) ?>"><?= field_error('city') ?></div>
      <div class="<?= field_class('province') ?>"><label for="province">Province</label><input class="input" id="province" name="province" value="<?= e(old('province', $u['province'])) ?>"><?= field_error('province') ?></div>
    </div>
    <div class="grid-2">
      <div class="<?= field_class('region') ?>"><label for="region">Region</label>
        <select class="input" id="region" name="region"><option value="">Select region</option>
          <?php foreach ($regions as $k => $r): ?><option value="<?= e($k) ?>" <?= old('region', $u['region']) === $k ? 'selected' : '' ?>><?= e($r['label']) ?></option><?php endforeach; ?>
        </select><?= field_error('region') ?></div>
      <div class="<?= field_class('postal_code') ?>"><label for="postal_code">ZIP code</label><input class="input" id="postal_code" name="postal_code" inputmode="numeric" maxlength="4" value="<?= e(old('postal_code', $u['postal_code'])) ?>"><?= field_error('postal_code') ?></div>
    </div>
    <button class="btn" type="submit">Save changes</button>
  </form>

  <form class="hud" action="<?= e(url('/account/password')) ?>" method="post" novalidate>
    <?= csrf_field() ?>
    <h2>Change password</h2>
    <div class="<?= field_class('current_password') ?>"><label for="current_password">Current password</label><input class="input" id="current_password" name="current_password" type="password" autocomplete="current-password"><?= field_error('current_password') ?></div>
    <div class="<?= field_class('password') ?>"><label for="password">New password</label><input class="input" id="password" name="password" type="password" autocomplete="new-password"><?= field_error('password') ?></div>
    <div class="field"><label for="password_confirmation">Confirm new password</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"></div>
    <button class="btn btn--ghost" type="submit">Change password</button>
    <p class="hint" style="margin-top:1rem">Member since <?= e(date('F Y', strtotime($u['created_at']))) ?></p>
  </form>
</div>
<?php if (!$seller): ?></div><?php endif; ?>
