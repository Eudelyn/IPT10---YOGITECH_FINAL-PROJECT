<h1 style="margin:0 0 10px;font-size:24px;color:#fcee0a">Password <?= $how === 'reset' ? 'reset' : 'changed' ?></h1>
<p style="margin:0 0 14px;font-size:15px;line-height:1.5">Hi <?= e(explode(' ', $name)[0]) ?>, the password for your Yogitech account was <?= $how === 'reset' ? 'reset using a reset link' : 'changed in Account settings' ?>.</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="font-size:14px;margin:0 0 16px;color:#ece9f8">
  <tr><td style="padding:2px 14px 2px 0;color:#a59fc4">When</td><td><?= e($when) ?></td></tr>
  <tr><td style="padding:2px 14px 2px 0;color:#a59fc4">IP address</td><td><?= e($ip) ?></td></tr>
</table>
<p style="margin:0 0 14px;font-size:15px;line-height:1.5">For your safety, every other device signed in to your account was signed out.</p>
<p style="margin:0 0 14px;font-size:15px;line-height:1.5"><b>Wasn't you?</b> Reset your password right away.</p>
<p style="margin:22px 0 0"><a href="<?= e($cta) ?>" style="display:inline-block;background:#fcee0a;color:#0c0a17;text-decoration:none;font-weight:bold;padding:12px 20px">Reset password</a></p>
