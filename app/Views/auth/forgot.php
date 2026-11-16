<h1>Forgot password</h1>
<p class="muted">Enter the email you signed up with. We'll send you a link to choose a new password.</p>
<form action="<?= e(url('/forgot-password')) ?>" method="post" novalidate>
  <?= csrf_field() ?>
  <div class="<?= field_class('email') ?>">
    <label for="email">Email</label>
    <input class="input" id="email" name="email" type="email" autocomplete="email" required value="<?= e(old('email')) ?>" autofocus>
    <?= field_error('email') ?>
  </div>
  <button class="btn btn--block" type="submit">Send reset link</button>
</form>
<p class="muted" style="margin-top:1.25rem">Remembered it? <a href="<?= e(url('/login')) ?>">Back to sign in</a></p>
<?php if (!empty($devMail)): ?>
  <p class="hint" style="margin-top:1rem;border:1px dashed var(--line-2);padding:.6rem .8rem">
    Developer note: <code>MAIL_DRIVER=log</code>, so reset emails are saved, not sent.
    <a href="<?= e(url('/dev/mail')) ?>">Open the developer mailbox</a> to click the link, or set up the Gmail API to send for real.
  </p>
<?php endif; ?>
