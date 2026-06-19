<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'User.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Listing.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'MarketplaceReview.php';

class SellerController extends Controller
{
    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $seller = $id > 0 ? (new User())->sellerProfile($id) : null;
        if ($seller === null) {
            http_response_code(404);
            echo '404 - Seller not found';
            return;
        }
        $reviews = new MarketplaceReview();
        $this->view('sellers.show', [
            'title' => $seller['username'],
            'seller' => $seller,
            'listings' => (new Listing())->activeForSeller($id),
            'reviews' => $reviews->forSeller($id),
            'metrics' => $reviews->metrics($id),
        ]);
    }
}
