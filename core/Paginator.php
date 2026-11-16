<?php
declare(strict_types=1);

namespace Core;

/**
 * Pagination math + link builder that keeps the current query string.
 */
final class Paginator
{
    public int $total;
    public int $perPage;
    public int $page;
    public int $lastPage;
    public array $items = [];

    public function __construct(int $total, int $perPage, int $page)
    {
        $this->total = $total;
        $this->perPage = max(1, $perPage);
        $this->lastPage = max(1, (int) ceil($total / $this->perPage));
        $this->page = min(max(1, $page), $this->lastPage);
    }

    /** Paginates any SELECT (without LIMIT). Counts with a subquery, then fetches one page. */
    public static function query(string $sql, array $params, int $page, int $perPage = 20): self
    {
        $total = (int) Database::scalar('SELECT COUNT(*) FROM (' . $sql . ') AS paged', $params);
        $pager = new self($total, $perPage, $page);
        $pager->items = Database::select($sql . ' LIMIT ' . (int) $pager->perPage . ' OFFSET ' . (int) $pager->offset(), $params);
        return $pager;
    }

    public function offset(): int { return ($this->page - 1) * $this->perPage; }
    public function from(): int { return $this->total === 0 ? 0 : $this->offset() + 1; }
    public function to(): int { return min($this->total, $this->offset() + $this->perPage); }

    public function url(int $page): string
    {
        $query = $_GET;
        $query['page'] = $page;
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        return $path . '?' . http_build_query($query);
    }

    /** Page numbers to show, with null as an ellipsis gap. */
    public function window(int $around = 2): array
    {
        $pages = [];
        for ($i = 1; $i <= $this->lastPage; $i++) {
            if ($i === 1 || $i === $this->lastPage || abs($i - $this->page) <= $around) {
                $pages[] = $i;
            } elseif (end($pages) !== null) {
                $pages[] = null;
            }
        }
        return $pages;
    }
}
