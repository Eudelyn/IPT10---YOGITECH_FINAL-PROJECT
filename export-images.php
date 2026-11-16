<?php
/**
 * Saves every product photo from DummyJSON into product-images/ so it can be zipped and sent.
 * Run:  docker compose exec app php export-images.php
 */
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Core\Database;

echo "Reading products...\n";
$rows = Database::select('SELECT thumbnail, images FROM products');
$urls = [];
foreach ($rows as $row) {
    $urls[] = $row['thumbnail'];
    foreach (json_decode((string) $row['images'], true) ?: [] as $u) {
        $urls[] = $u;
    }
}
$urls = array_unique(array_filter($urls, fn($u) => is_string($u) && str_starts_with($u, 'https://cdn.dummyjson.com/')));
echo "Found " . count($urls) . " images. Downloading...\n";

$saved = 0;
foreach ($urls as $url) {
    $path = __DIR__ . '/product-images/' . substr(parse_url($url, PHP_URL_PATH), 1);
    if (is_file($path)) { $saved++; continue; }
    @mkdir(dirname($path), 0777, true);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 30]);
    $data = curl_exec($ch);
    $ok = $data !== false && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    if ($ok) { file_put_contents($path, $data); $saved++; } else { echo "failed: $url\n"; }
}
echo "Saved $saved of " . count($urls) . " images in the product-images folder. Zip that folder and send it.\n";