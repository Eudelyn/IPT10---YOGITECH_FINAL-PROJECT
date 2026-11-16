<h1 style="margin:0 0 10px;font-size:24px;color:#fcee0a">Email address changed</h1>
<p style="margin:0 0 14px;font-size:15px;line-height:1.5">Hi <?= e(explode(' ', $name)[0]) ?>, the email on your Yogitech account was changed to <b><?= e($newEmail) ?></b>. Order updates will go there from now on.</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="font-size:14px;margin:0 0 16px;color:#ece9f8">
  <tr><td style="padding:2px 14px 2px 0;color:#a59fc4">When</td><td><?= e($when) ?></td></tr>
  <tr><td style="padding:2px 14px 2px 0;color:#a59fc4">IP address</td><td><?= e($ip) ?></td></tr>
</table>
<p style="margin:0;font-size:15px;line-height:1.5"><b>Wasn't you?</b> Reply to this email so we can help you get your account back.</p>
