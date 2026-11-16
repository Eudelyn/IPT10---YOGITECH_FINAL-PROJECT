<!doctype html>
<html lang="en">
<head><?= \Core\View::partial('partials/head', ['title' => 'Error ' . $status]) ?></head>
<body>
<main class="error-page">
  <div>
    <p class="wordmark" data-glitch><?= (int) $status ?></p>
    <h1><?= e($message) ?></h1>
    <?php if (!empty($hint)): ?>
      <p class="error-page__fix"><?= e($hint) ?></p>
      <?php if (!empty($canRepair)): ?>
        <form action="<?= e(url('/setup/repair-database')) ?>" method="post" style="margin:1rem 0">
          <?= csrf_field() ?>
          <button class="btn" type="submit">Repair database now</button>
          <span class="hint" style="display:block;margin-top:.4rem">Creates the missing tables and columns. Your data is kept. (Only shown on this computer while APP_DEBUG=true.)</span>
        </form>
      <?php endif; ?>
    <?php else: ?>
      <p class="muted"><?= $status === 404 ? 'Check the address, or search for a gadget from the home page.' : ($status === 403 ? 'Sign in with an account that can open this page.' : ($status === 419 ? 'Forms expire for your security. Reload the page and try again.' : 'Go back and try again. If it keeps happening, send us the error code below.')) ?></p>
    <?php endif; ?>
    <div class="error-page__actions">
      <a class="btn" href="<?= e(url('/')) ?>">Go to the home page</a>
      <button class="btn btn--ghost" type="button" onclick="history.length > 1 ? history.back() : location.reload()">Go back</button>
    </div>
    <?php if (!empty($ref)): ?><p class="small muted" style="margin-top:1rem">Error code: <b><?= e($ref) ?></b></p><?php endif; ?>
    <?php if (!empty($exception)): ?>
      <details class="trace"<?= empty($hint) ? ' open' : '' ?>><summary>Technical details (shown because APP_DEBUG=true)</summary><b style="color:var(--magenta)"><?= e(get_class($exception)) ?>: <?= e($exception->getMessage()) ?></b>
<?= e($exception->getFile() . ':' . $exception->getLine()) ?>

<?= e($exception->getTraceAsString()) ?></details>
    <?php endif; ?>
  </div>
</main>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
