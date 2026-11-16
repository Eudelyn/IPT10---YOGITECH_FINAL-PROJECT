<?php
declare(strict_types=1);

namespace App\Services;

use Core\Cache;
use Core\HttpClient;

/**
 * External API #2 — ExchangeRate-API (open.er-api.com), no key required.
 * Converts the USD prices from the product API into Philippine pesos.
 * Cached for 12 hours; falls back to FALLBACK_USD_PHP when the API is unreachable.
 */
final class ExchangeRateService
{
    private const CACHE_KEY = 'fx:usd_php';

    public static function info(): array
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_array($cached)) {
            return $cached;
        }

        $res = (new HttpClient(8, 1))->get((string) config('services.exchange_api.url'));
        $json = $res['json'] ?? [];
        if ($res['ok'] && ($json['result'] ?? '') === 'success' && isset($json['rates']['PHP'])) {
            $info = [
                'rate'       => round((float) $json['rates']['PHP'], 4),
                'source'     => 'live',
                'provider'   => 'ExchangeRate-API',
                'updated_at' => $json['time_last_update_utc'] ?? gmdate('D, d M Y H:i:s') . ' +0000',
            ];
            Cache::put(self::CACHE_KEY, $info, (int) config('services.exchange_api.cache_ttl', 43200));
            return $info;
        }

        // Don't hammer a failing API: cache the fallback briefly.
        $info = [
            'rate'       => (float) config('services.exchange_api.fallback', 58.50),
            'source'     => 'fallback',
            'provider'   => 'Configured fallback rate',
            'updated_at' => date('D, d M Y H:i:s O'),
            'error'      => $res['error'] ?? 'Unexpected response',
        ];
        Cache::put(self::CACHE_KEY, $info, 600);
        return $info;
    }

    public static function rate(): float
    {
        return (float) self::info()['rate'];
    }

    /**
     * Converts USD to a store-friendly peso price.
     * Under ₱100 → nearest peso; otherwise rounded to end in 9 (₱11,599).
     */
    public static function toPeso(float $usd, ?float $rate = null): float
    {
        $php = $usd * ($rate ?? self::rate());
        if ($php < 100) {
            return max(1.0, round($php));
        }
        return (float) (round($php / 10) * 10 - 1);
    }
}
