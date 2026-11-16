<div class="wrap" style="max-width:980px">
  <div class="page-head"><div><h1>Developer mailbox</h1><p>Only visible on this computer while <code>APP_DEBUG=true</code>.</p></div></div>

  <div class="notice">
    <?= icon('bolt') ?>
    <p>
      <?php if ($driver === 'log'): ?>
        <b>Emails are not being sent.</b> <code>MAIL_DRIVER=log</code> saves each email here instead. To deliver to Gmail,
        set up the Gmail API (README → “Gmail API”) and run <code>php yogi gmail:auth</code>.
      <?php else: ?>
        Sending with <b><?= e($driver) ?></b>. A copy of every failed email is also kept below.
      <?php endif; ?>
    </p>
  </div>

  <section class="hud">
    <h2>Recent sends</h2>
    <?php if (!$logs): ?><p class="muted">No emails yet.</p><?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>When</th><th>To</th><th>Subject</th><th>Driver</th><th>Result</th></tr></thead>
      <tbody>
      <?php foreach ($logs as $l): ?>
        <tr>
          <td class="small"><?= e(date('M j, g:i A', strtotime($l['created_at']))) ?></td>
          <td><?= e($l['to_email']) ?></td>
          <td><?= e($l['subject']) ?></td>
          <td><?= e($l['driver']) ?></td>
          <td><?php if ($l['status'] === 'failed'): ?><b style="color:var(--magenta)">failed</b><br><span class="small"><?= e($l['error']) ?></span><?php else: ?><?= e($l['status'] === 'logged' ? 'saved here (not sent)' : $l['status']) ?><?php endif; ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </section>

  <section class="hud">
    <h2>Saved emails</h2>
    <?php if (!$mails): ?><p class="muted">Nothing saved yet.</p><?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Saved</th><th>To</th><th>Subject</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($mails as $m): ?>
        <tr><td class="small"><?= e($m['at']) ?></td><td><?= e($m['to']) ?></td><td><?= e($m['subject']) ?></td>
          <td><a class="btn btn--sm btn--ghost" href="<?= e(url('/dev/mail/' . $m['file'])) ?>" target="_blank" rel="noopener">Open</a></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </section>
</div>
