<?php /** @var Core\Paginator $pager */ if ($pager->lastPage > 1): ?>
<nav class="pagination" aria-label="Pages">
  <?php if ($pager->page > 1): ?><a href="<?= e($pager->url($pager->page - 1)) ?>" aria-label="Previous page">‹</a><?php endif; ?>
  <?php foreach ($pager->window() as $n): ?>
    <?php if ($n === null): ?><span class="gap">…</span>
    <?php elseif ($n === $pager->page): ?><span class="is-current" aria-current="page"><?= $n ?></span>
    <?php else: ?><a href="<?= e($pager->url($n)) ?>"><?= $n ?></a><?php endif; ?>
  <?php endforeach; ?>
  <?php if ($pager->page < $pager->lastPage): ?><a href="<?= e($pager->url($pager->page + 1)) ?>" aria-label="Next page">›</a><?php endif; ?>
</nav>
<?php endif; ?>
