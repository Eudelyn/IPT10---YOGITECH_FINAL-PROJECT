<h1>Sign in</h1>
<p class="muted">New to Yogitech? <a href="<?= e(url('/register')) ?>">Create an account</a></p>
<form action="<?= e(url('/login')) ?>" method="post" novalidate>
  <?= csrf_field() ?>
  <div class="<?= field_class('email') ?>">
    <label for="email">Email</label>
    <input class="input" id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>" autofocus>
    <?= field_error('email') ?>
  </div>
  <div class="<?= field_class('password') ?>">
    <label for="password" style="display:flex;justify-content:space-between;gap:1rem">Password <a href="<?= e(url('/forgot-password')) ?>" style="font-weight:400">Forgot password?</a></label>
    <input class="input" id="password" name="password" type="password" autocomplete="current-password" required>
    <?= field_error('password') ?>
  </div>
  <button class="btn btn--block" type="submit">Sign in</button>
</form>
