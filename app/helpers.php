<?php
declare(strict_types=1);

/**
 * App-specific helpers used by the views.
 */

use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use App\Services\ExchangeRateService;
use App\Services\ShippingService;
use Core\Auth;

function nav_categories(): array
{
    static $cats = null;
    if ($cats === null) {
        try {
            $cats = Category::ordered();
        } catch (Throwable $e) {
            $cats = [];
        }
    }
    return $cats;
}

function cart_count(): int
{
    static $count = null;
    if ($count === null) {
        $count = Auth::is('buyer') ? CartService::count((int) Auth::id()) : 0;
    }
    return $count;
}

function fx_info(): array
{
    try {
        return ExchangeRateService::info();
    } catch (Throwable $e) {
        return ['rate' => (float) config('services.exchange_api.fallback', 58.5), 'source' => 'fallback'];
    }
}

function discount_pct(array $product): int { return Product::discountPercent($product); }
function status_label(string $status): string { return ShippingService::label($status); }

function payment_label(string $method): string
{
    return ['cod' => 'Cash on delivery', 'gcash' => 'GCash', 'card' => 'Credit / debit card'][$method] ?? $method;
}

/**
 * Understands a product video value: a YouTube link (watch, youtu.be, shorts, embed)
 * or an uploaded / direct .mp4 or .webm file. Returns null for anything else.
 * @return array{type: string, id?: string, src: string, watch: string, poster?: string}|null
 */
function product_video(?string $value): ?array
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    if (preg_match('#^https?://(?:www\.|m\.)?(?:youtube\.com/(?:watch\?(?:.*&)?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $value, $m)) {
        return [
            'type' => 'youtube', 'id' => $m[1],
            'src' => 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&modestbranding=1&playsinline=1&enablejsapi=1',
            'watch' => 'https://www.youtube.com/watch?v=' . $m[1],
            'poster' => 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg',
        ];
    }
    if (preg_match('#^uploads/products/videos/[a-f0-9]{16}\.(mp4|webm)$#', $value)) {
        return ['type' => 'file', 'src' => asset($value), 'watch' => asset($value)];
    }
    if (preg_match('#^https?://[^\s"<>]+\.(mp4|webm)(\?[^\s"<>]*)?$#i', $value)) {
        return ['type' => 'file', 'src' => $value, 'watch' => $value];
    }
    return null;
}

/** Name of the active online payment gateway, for buyer-facing text. */
function payment_gateway_name(): string
{
    return config('payments.gateway') === 'paymongo' ? 'PayMongo (test mode)' : 'sandbox payment';
}

/** Inline SVG icons (stroke style) used across the UI. */
function icon(string $name, int $size = 20): string
{
    $paths = [
        'phone'   => '<rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M11 18.5h2"/>',
        'laptop'  => '<rect x="4" y="4.5" width="16" height="11" rx="1.5"/><path d="M2 19.5h20"/>',
        'tablet'  => '<rect x="4.5" y="2.5" width="15" height="19" rx="2"/><path d="M11 18.5h2"/>',
        'plug'    => '<path d="M9 2v5M15 2v5M6 7h12v4a6 6 0 0 1-12 0zM12 17v5"/>',
        'chip'    => '<rect x="6" y="6" width="12" height="12" rx="1"/><path d="M9 2v4M15 2v4M9 18v4M15 18v4M2 9h4M2 15h4M18 9h4M18 15h4"/>',
        'cart'    => '<path d="M3 3h2l2.4 11.2a1.5 1.5 0 0 0 1.5 1.2h8.6a1.5 1.5 0 0 0 1.5-1.1L21 7H6"/><circle cx="9.5" cy="20" r="1.3"/><circle cx="17.5" cy="20" r="1.3"/>',
        'heart'   => '<path d="M12 20s-7.5-4.6-9.2-9.3A4.9 4.9 0 0 1 12 6.4a4.9 4.9 0 0 1 9.2 4.3C19.5 15.4 12 20 12 20z"/>',
        'search'  => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'truck'   => '<path d="M2 6h11v10H2zM13 9h4l4 4v3h-8z"/><circle cx="6" cy="18" r="1.8"/><circle cx="17" cy="18" r="1.8"/>',
        'shield'  => '<path d="M12 2.5 4 5.5v6c0 5 3.4 8.6 8 10 4.6-1.4 8-5 8-10v-6z"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
        'return'  => '<path d="M9 14 4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-3"/>',
        'wallet'  => '<rect x="2.5" y="5.5" width="19" height="14" rx="2"/><path d="M16 12.5h2.5M2.5 9.5h19"/>',
        'box'     => '<path d="m12 2.5 9 4.5v10l-9 4.5-9-4.5V7z"/><path d="m3 7 9 4.5L21 7M12 11.5v10"/>',
        'pin'     => '<path d="M12 21s7-6.2 7-11.5a7 7 0 0 0-14 0C5 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'grid'    => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>',
        'list'    => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'chart'   => '<path d="M3 21h18M6 17V10M11 17V5M16 17v-4M21 17V8"/>',
        'download'=> '<path d="M12 3v12m0 0-4.5-4.5M12 15l4.5-4.5M4 20h16"/>',
        'logout'  => '<path d="M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10"/>',
        'star'    => '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z"/>',
        'tag'     => '<path d="M3 12V3h9l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'menu'    => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'x'       => '<path d="M6 6l12 12M18 6 6 18"/>',
        'plus'    => '<path d="M12 5v14M5 12h14"/>',
        'edit'    => '<path d="M4 20h4L19 9l-4-4L4 16z"/>',
        'trash'   => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'check'   => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'store'   => '<path d="M3 9.5 4.5 4h15L21 9.5M3 9.5h18M3 9.5a3 3 0 0 0 6 0 3 3 0 0 0 6 0 3 3 0 0 0 6 0M5 12v8h14v-8"/>',
        'bolt'    => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
    ];
    $p = $paths[$name] ?? $paths['chip'];
    return '<svg class="ico" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function product_img(?string $src, string $alt = '', string $attrs = ''): string
{
    $fallback = url('assets/img/placeholder.svg');
    return '<img src="' . e(img_url($src)) . '" alt="' . e($alt) . '" loading="lazy" ' . $attrs
        . ' onerror="this.onerror=null;this.src=\'' . e($fallback) . '\'">';
}

function status_badge(string $status): string
{
    return '<span class="status status--' . e($status) . '">' . e(status_label($status)) . '</span>';
}

function field_error(string $key): string
{
    $msg = errors($key);
    return $msg ? '<span class="error-text" role="alert">' . e($msg) . '</span>' : '';
}

function field_class(string $key): string
{
    return errors($key) ? 'field has-error' : 'field';
}
