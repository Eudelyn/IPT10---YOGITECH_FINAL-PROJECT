<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;
use App\Models\Review;
use Core\Auth;
use Core\Controller;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Session;

final class ReviewController extends Controller
{
    public function store(Request $request, string $id): Response
    {
        $productId = (int) $id;
        $user = Auth::user();
        $orderId = Review::eligibleOrderId((int) $user['id'], $productId);
        if (!$orderId) {
            Session::flash('error', 'You can review a gadget after its order is delivered.');
            return $this->back();
        }
        if (Review::existsFor((int) $user['id'], $productId)) {
            Session::flash('info', 'You already reviewed this product.');
            return $this->back();
        }
        $data = $this->validate($request, ['rating' => 'required|integer|between:1,5', 'comment' => 'required|min:5|max:1000']);
        Database::insert('reviews', [
            'product_id' => $productId, 'user_id' => $user['id'], 'order_id' => $orderId,
            'reviewer_name' => $user['name'], 'rating' => (int) $data['rating'], 'comment' => $data['comment'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Product::refreshRating($productId);
        Session::flash('success', 'Review posted. Thanks!');
        return $this->redirect('/products/' . $productId . '#reviews');
    }
}
