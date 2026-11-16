<h1>Choose a new password</h1>
<p class="muted">For <b><?= e($email) ?></b>. Use at least 8 characters with a letter and a number.</p>
<form action="<?= e(url('/reset-password')) ?>" method="post" novalidate data-validate>
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= e($token) ?>">
  <input type="hidden" name="email" value="<?= e($email) ?>" autocomplete="username">
  <div class="<?= field_class('password') ?>">
    <label for="password">New password</label>
    <input class="input" id="password" name="password" type="password" autocomplete="new-password" required minlength="8" autofocus>
    <?= field_error('password') ?>
  </div>
  <div class="field">
    <label for="password_confirmation">Confirm new password</label>
    <input class="input" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
  </div>
  <button class="btn btn--block" type="submit">Update password</button>
</form>
<p class="muted" style="margin-top:1.25rem">Signing in will be required again on all your devices.</p>
