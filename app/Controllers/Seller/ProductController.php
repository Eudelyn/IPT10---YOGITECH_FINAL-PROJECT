<?php
declare(strict_types=1);

namespace App\Controllers\Seller;

use App\Models\Category;
use App\Models\Product;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;

final class ProductController extends Controller
{
    private const RULES = [
        'name'          => 'required|min:3|max:180',
        'category_id'   => 'required|integer|exists:categories,id',
        'brand'         => 'nullable|max:80',
        'sku'           => 'nullable|max:60',
        'description'   => 'required|min:10|max:5000',
        'price'         => 'required|numeric|min:1|max:9999999',
        'compare_price' => 'nullable|numeric|min:0|max:9999999',
        'stock'         => 'required|integer|min:0|max:100000',
        'thumbnail_url' => 'nullable|url|max:500',
        'image_urls'    => 'nullable|max:3000',
        'video_url'     => 'nullable|max:500',
        'warranty'      => 'nullable|max:120',
        'return_policy' => 'nullable|max:120',
        'status'        => 'required|in:active,draft',
    ];

    private function owned(string $id): array
    {
        $p = Product::find((int) $id);
        if (!$p || (int) $p['seller_id'] !== Auth::id()) {
            abort(404);
        }
        return $p;
    }

    public function index(Request $request): Response
    {
        $q = (string) $request->query('q', '');
        $filter = (string) $request->query('filter', '');
        $sql = 'SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.seller_id = ?';
        $params = [Auth::id()];
        if ($q !== '') {
            $sql .= ' AND (p.name LIKE ? OR p.sku LIKE ? OR p.brand LIKE ?)';
            $like = '%' . like_escape($q) . '%';
            array_push($params, $like, $like, $like);
        }
        if ($filter === 'low') {
            $sql .= ' AND p.stock <= ' . (int) config('app.low_stock', 5);
        } elseif (in_array($filter, ['active', 'draft'], true)) {
            $sql .= ' AND p.status = ?';
            $params[] = $filter;
        }
        return $this->view('seller/products/index', [
            'title' => 'Products',
            'pager' => $pager = \Core\Paginator::query($sql . ' ORDER BY p.updated_at DESC, p.id DESC', $params, (int) $request->query('page', 1), 25),
            'products' => $pager->items,
            'q' => $q, 'filter' => $filter,
        ], 'layouts/seller');
    }

    public function create(Request $request): Response
    {
        return $this->view('seller/products/form', [
            'title' => 'Add product', 'product' => null, 'categories' => Category::ordered(),
        ], 'layouts/seller');
    }

    public function edit(Request $request, string $id): Response
    {
        return $this->view('seller/products/form', [
            'title' => 'Edit product', 'product' => $this->owned($id), 'categories' => Category::ordered(),
        ], 'layouts/seller');
    }

    public function store(Request $request): Response
    {
        $data = $this->validate($request, self::RULES, ['category_id' => 'Category', 'compare_price' => 'Original price']);
        $row = $this->rowFrom($request, $data);
        $row['seller_id'] = Auth::id();
        $row['source'] = 'manual';
        $id = Product::create($row);
        Session::flash('success', 'Product added. It\'s ' . ($row['status'] === 'active' ? 'live in the store.' : 'saved as a draft.'));
        return $this->redirect('/seller/products/' . $id . '/edit');
    }

    public function update(Request $request, string $id): Response
    {
        $product = $this->owned($id);
        $data = $this->validate($request, self::RULES, ['category_id' => 'Category', 'compare_price' => 'Original price']);
        $row = $this->rowFrom($request, $data, $product);
        Product::updateById((int) $product['id'], $row);
        if ($row['thumbnail'] !== $product['thumbnail']) {
            self::deleteUpload($product['thumbnail']); // old uploaded image is no longer used
        }
        if (($row['video_url'] ?? null) !== ($product['video_url'] ?? null)) {
            self::deleteUpload($product['video_url'] ?? null); // old uploaded video is no longer used
        }
        Session::flash('success', 'Changes saved.');
        return $this->redirect('/seller/products/' . $id . '/edit');
    }

    public function destroy(Request $request, string $id): Response
    {
        $product = $this->owned($id);
        $open = (int) Database::scalar(
            "SELECT COUNT(DISTINCT o.id) FROM order_items i JOIN orders o ON o.id = i.order_id
              WHERE i.product_id = ? AND o.status NOT IN ('delivered','cancelled')",
            [$product['id']]
        );
        if ($open > 0) {
            Session::flash('error', $product['name'] . ' is in ' . $open . ' open order(s). Set it to Draft to hide it, and delete it after those orders are delivered or cancelled.');
            return $this->redirect('/seller/products/' . $product['id'] . '/edit');
        }
        Product::deleteById((int) $product['id']);
        self::deleteUpload($product['thumbnail']);
        Session::flash('success', 'Deleted ' . $product['name'] . '. Past orders keep their item details.');
        return $this->redirect('/seller/products');
    }

    private function rowFrom(Request $request, array $data, ?array $existing = null): array
    {
        $compare = $data['compare_price'] !== null && (float) $data['compare_price'] > (float) $data['price'] ? (float) $data['compare_price'] : null;
        $images = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($data['image_urls'] ?? ''))), static fn($u) => filter_var($u, FILTER_VALIDATE_URL)));

        $thumbnail = $existing['thumbnail'] ?? null;
        if (!empty($data['thumbnail_url'])) {
            $thumbnail = $data['thumbnail_url'];
        }
        $upload = $request->file('thumbnail_file');
        if ($upload) {
            $thumbnail = $this->saveUpload($upload);
        }

        // Video: uploaded file > pasted link > keep the current one. "Remove video" clears it.
        $video = $existing['video_url'] ?? null;
        $link = trim((string) ($data['video_url'] ?? ''));
        if ($link !== '' && $link !== $video) {
            if (!product_video($link) || !preg_match('#^https?://#i', $link)) {
                Session::flash('errors', ['video_url' => 'Paste a YouTube link (youtube.com/watch?v=…, youtu.be/…) or a link ending in .mp4 or .webm.']);
                Session::flash('old', $request->only(array_keys(self::RULES)));
                throw new \Core\Exceptions\HttpResponseException(back());
            }
            $video = $link;
        } elseif ($link === '' && $video !== null && preg_match('#^https?://#i', $video)) {
            $video = null; // link field emptied
        }
        if ($request->input('remove_video')) {
            $video = null;
        }
        $videoFile = $request->file('video_file');
        if ($videoFile) {
            $video = $this->saveVideo($videoFile);
        }

        return [
            'category_id' => (int) $data['category_id'], 'name' => $data['name'], 'brand' => $data['brand'] ?: 'Generic',
            'sku' => $data['sku'] ?: null, 'description' => $data['description'], 'price' => round((float) $data['price'], 2),
            'compare_price' => $compare, 'stock' => (int) $data['stock'], 'thumbnail' => $thumbnail,
            'images' => json_encode($images, JSON_UNESCAPED_SLASHES), 'video_url' => $video, 'warranty' => $data['warranty'] ?: null,
            'return_policy' => $data['return_policy'] ?: null, 'status' => $data['status'],
        ];
    }

    /** Removes a file we stored in public/uploads/products (never remote URLs or other paths). */
    private static function deleteUpload(?string $path): void
    {
        if ($path && preg_match('#^uploads/products/videos/[a-f0-9]{16}\.(mp4|webm)$#', $path)) {
            @unlink(BASE_PATH . '/public/' . $path);
            return;
        }
        if ($path && preg_match('#^uploads/products/[a-f0-9]{16}\.(jpg|png|webp|gif)$#', $path)) {
            $stillUsed = (int) Database::scalar('SELECT COUNT(*) FROM order_items WHERE thumbnail = ?', [$path]);
            if ($stillUsed === 0) {
                @unlink(BASE_PATH . '/public/' . $path);
            }
        }
    }

    /** Validates and stores an uploaded MP4/WebM video in public/uploads/products/videos. */
    private function saveVideo(array $file): string
    {
        $fail = static function (string $msg) {
            Session::flash('errors', ['video_file' => $msg]);
            throw new \Core\Exceptions\HttpResponseException(back());
        };
        $max = self::maxVideoBytes();
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            $fail('The video is too big. Keep it under ' . round($max / 1048576) . ' MB, or paste a YouTube link instead.');
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $fail('Video upload failed (error ' . $file['error'] . ').');
        }
        if ($file['size'] > $max) {
            $fail('The video must be ' . round($max / 1048576) . ' MB or smaller. Paste a YouTube link for longer videos.');
        }
        $mime = function_exists('finfo_open') ? (string) finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']) : '';
        $ext = ['video/mp4' => 'mp4', 'video/webm' => 'webm'][$mime] ?? null;
        if (!$ext) {
            $fail('Upload an MP4 or WebM video.');
        }
        $dir = BASE_PATH . '/public/uploads/products/videos';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
            $fail('Couldn\'t save the video. Check that public/uploads is writable.');
        }
        return 'uploads/products/videos/' . $name;
    }

    /** 30 MB, or less if PHP's upload limits (php.ini) are lower. */
    public static function maxVideoBytes(): int
    {
        $toBytes = static function (string $v): int {
            $n = (int) $v;
            switch (strtolower(substr(trim($v), -1))) {
                case 'g': return $n * 1073741824;
                case 'm': return $n * 1048576;
                case 'k': return $n * 1024;
            }
            return $n;
        };
        $limits = [30 * 1048576];
        foreach (['upload_max_filesize', 'post_max_size'] as $key) {
            $b = $toBytes((string) ini_get($key));
            if ($b > 0) {
                $limits[] = $b;
            }
        }
        return min($limits);
    }

    /** Validates and stores an uploaded image in public/uploads/products. */
    private function saveUpload(array $file): string
    {
        $fail = static function (string $msg) {
            Session::flash('errors', ['thumbnail_file' => $msg]);
            throw new \Core\Exceptions\HttpResponseException(back());
        };
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $fail('Upload failed (error ' . $file['error'] . '). Try a smaller image.');
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            $fail('Image must be 2 MB or smaller.');
        }
        $info = @getimagesize($file['tmp_name']);
        $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'][$info['mime'] ?? ''] ?? null;
        if (!$ext) {
            $fail('Upload a JPG, PNG, WebP or GIF image.');
        }
        $dir = BASE_PATH . '/public/uploads/products';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
            $fail('Couldn\'t save the image. Check that public/uploads is writable.');
        }
        return 'uploads/products/' . $name;
    }
}
