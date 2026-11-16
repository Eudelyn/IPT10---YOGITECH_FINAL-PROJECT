<h1 style="margin:0 0 10px;font-size:24px;color:#fcee0a">Reset your password</h1>
<p style="margin:0 0 14px;font-size:15px;line-height:1.5">Hi <?= e(explode(' ', $name)[0]) ?>, someone (hopefully you) asked to reset the password for your Yogitech account.</p>
<p style="margin:0 0 14px;font-size:15px;line-height:1.5">The link works once and expires in <?= (int) $minutes ?> minutes.</p>
<p style="margin:22px 0"><a href="<?= e($cta) ?>" style="display:inline-block;background:#fcee0a;color:#0c0a17;text-decoration:none;font-weight:bold;padding:12px 20px">Choose a new password</a></p>
<p style="margin:0 0 8px;font-size:13px;line-height:1.5;color:#a59fc4">If the button doesn't work, paste this link into your browser:<br><span style="color:#00f0ff;word-break:break-all"><?= e($cta) ?></span></p>
<p style="margin:14px 0 0;font-size:13px;line-height:1.5;color:#a59fc4">Didn't ask for this? Ignore this email. Your password stays the same.</p>
