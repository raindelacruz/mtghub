<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'BuylistOffer.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'CartItem.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Listing.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'WishlistItem.php';
require_once APP_PATH . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'NotificationService.php';

class BuylistOfferController extends Controller
{
    private BuylistOffer $offers;
    private CartItem $cartItems;
    private Listing $listings;
    private WishlistItem $buylist;

    public function __construct()
    {
        $this->offers = new BuylistOffer();
        $this->cartItems = new CartItem();
        $this->listings = new Listing();
        $this->buylist = new WishlistItem();
    }

    public function store(): void
    {
        $this->requireLogin();

        $listingId = (int) ($_GET['listing_id'] ?? 0);
        $wishlistItemId = (int) ($_GET['wishlist_item_id'] ?? 0);
        $listing = $listingId > 0 ? $this->listings->find($listingId) : null;
        $buylistItem = $wishlistItemId > 0 ? $this->buylist->find($wishlistItemId) : null;
        $sellerId = (int) current_user()['id'];

        if ($listing === null || $buylistItem === null) {
            http_response_code(404);
            echo '404 - Wanted list match not found';
            exit;
        }

        if ((int) $listing['user_id'] !== $sellerId) {
            flash('error', 'You can only offer your own listings.');
            redirect('/listings/mine');
        }

        if ((int) $buylistItem['user_id'] === $sellerId || $this->normalizedCardName($buylistItem) !== $this->normalizedCardName($listing)) {
            flash('error', 'That wanted list request does not match this listing.');
            redirect('/listings/mine');
        }

        if ($listing['status'] !== 'active' || (int) $listing['quantity'] < 1) {
            flash('error', 'Only active listings with available quantity can be offered.');
            redirect('/listings/mine');
        }

        if ($buylistItem['max_price_php'] !== null && (float) $listing['price_php'] > (float) $buylistItem['max_price_php']) {
            flash('error', 'This listing is above the buyer max price.');
            redirect('/listings/mine');
        }

        if ($this->offers->hasPendingForListingAndBuylist((int) $listing['id'], (int) $buylistItem['id'])) {
            flash('error', 'You already sent a pending offer for that wanted list request.');
            redirect('/listings/mine');
        }

        $quantity = min((int) $listing['quantity'], (int) $buylistItem['desired_quantity']);
        $this->offers->create([
            'buyer_id' => (int) $buylistItem['user_id'],
            'seller_id' => $sellerId,
            'listing_id' => (int) $listing['id'],
            'wishlist_item_id' => (int) $buylistItem['id'],
            'quantity' => $quantity,
            'message' => trim($_POST['message'] ?? ''),
        ]);
        try {
            NotificationService::send((int) $buylistItem['user_id'], 'buylist_offer', 'New wanted-list offer', $listing['username'] . ' offered ' . $listing['card_name'] . ' for PHP ' . number_format((float) $listing['price_php'], 2) . '.', '/buylist');
        } catch (Throwable $exception) {
            error_log('MTGHub offer notification failed: ' . $exception->getMessage());
        }

        flash('success', 'Offer sent to the buyer.');
        redirect('/listings/mine');
    }

    public function accept(): void
    {
        $this->requireLogin();
        $buyerId = (int) current_user()['id'];
        $offer = $this->findPendingOfferForBuyer($buyerId);

        if ($offer['listing_status'] !== 'active' || (int) $offer['listing_quantity'] < 1
            || ($offer['seller_account_status'] ?? '') !== 'active' || empty($offer['seller_email_verified_at'])) {
            flash('error', 'That listing is no longer available.');
            redirect('/buylist');
        }

        if ($offer['max_price_php'] !== null && (float) $offer['price_php'] > (float) $offer['max_price_php']) {
            flash('error', 'That offer is now above your max buy price.');
            redirect('/buylist');
        }

        $quantity = min((int) $offer['quantity'], (int) $offer['listing_quantity']);
        $currentCartQuantity = $this->cartItems->quantityForListing($buyerId, (int) $offer['listing_id']);

        if ($quantity + $currentCartQuantity > (int) $offer['listing_quantity']) {
            flash('error', 'Your cart already has the available quantity for that listing.');
            redirect('/cart');
        }

        $cartItems = $this->cartItems->forBuyer($buyerId);
        $sellerIds = array_unique(array_map(static fn (array $item): int => (int) $item['seller_id'], $cartItems));

        if ($sellerIds !== [] && !in_array((int) $offer['seller_id'], $sellerIds, true)) {
            flash('error', 'Your cart can checkout one seller at a time. Clear or finish the current cart before accepting this offer.');
            redirect('/cart');
        }

        $this->cartItems->add($buyerId, (int) $offer['listing_id'], $quantity);
        $this->offers->updateStatus((int) $offer['id'], $buyerId, 'accepted');

        flash('success', 'Offer accepted and added to your cart.');
        redirect('/cart');
    }

    public function decline(): void
    {
        $this->requireLogin();
        $buyerId = (int) current_user()['id'];
        $offer = $this->findPendingOfferForBuyer($buyerId);
        $this->offers->updateStatus((int) $offer['id'], $buyerId, 'declined');

        flash('success', 'Offer declined.');
        redirect('/buylist');
    }

    private function requireLogin(): void
    {
        require_trade_access();
    }

    private function findPendingOfferForBuyer(int $buyerId): array
    {
        $id = (int) ($_GET['id'] ?? 0);
        $offer = $id > 0 ? $this->offers->findForBuyer($id, $buyerId) : null;

        if ($offer === null || $offer['status'] !== 'pending') {
            http_response_code(404);
            echo '404 - Pending seller offer not found';
            exit;
        }

        return $offer;
    }

    private function normalizedCardName(array $card): string
    {
        return mb_strtolower(trim((string) ($card['card_name'] ?? '')));
    }
}
