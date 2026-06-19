<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'CartItem.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Listing.php';

class CartController extends Controller
{
    private CartItem $cartItems;
    private Listing $listings;

    public function __construct()
    {
        $this->cartItems = new CartItem();
        $this->listings = new Listing();
    }

    public function index(): void
    {
        $this->requireLogin();
        $items = $this->cartItems->forBuyer((int) current_user()['id']);

        $this->view('cart.index', [
            'title' => 'Cart',
            'items' => $items,
            'summary' => $this->summary($items),
        ]);
    }

    public function add(): void
    {
        $this->requireLogin();
        $listing = $this->findPurchasableListing();
        $quantity = filter_var($_POST['quantity'] ?? '1', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        $currentCartQuantity = $this->cartItems->quantityForListing((int) current_user()['id'], (int) $listing['id']);

        if ($quantity === false || $quantity + $currentCartQuantity > (int) $listing['quantity']) {
            flash('error', 'Choose a quantity available from that listing.');
            redirect('/listings');
        }

        $cartItems = $this->cartItems->forBuyer((int) current_user()['id']);
        $sellerIds = array_unique(array_map(static fn (array $item): int => (int) $item['seller_id'], $cartItems));

        if ($sellerIds !== [] && !in_array((int) $listing['user_id'], $sellerIds, true)) {
            flash('error', 'Your cart can checkout one seller at a time. Clear or finish the current cart before adding another seller.');
            redirect('/cart');
        }

        $this->cartItems->add((int) current_user()['id'], (int) $listing['id'], (int) $quantity);
        flash('success', 'Card added to cart.');
        redirect('/cart');
    }

    public function update(): void
    {
        $this->requireLogin();
        $cartItemId = (int) ($_GET['id'] ?? 0);
        $quantity = filter_var($_POST['quantity'] ?? '1', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($cartItemId < 1 || $quantity === false) {
            flash('error', 'Invalid cart quantity.');
            redirect('/cart');
        }

        $cartItem = $this->cartItems->findForBuyerWithListing((int) current_user()['id'], $cartItemId);

        if ($cartItem === null) {
            flash('error', 'Cart item not found.');
            redirect('/cart');
        }

        if ($cartItem['listing_status'] !== 'active') {
            flash('error', $cartItem['card_name'] . ' is no longer active.');
            redirect('/cart');
        }

        if ((int) $quantity > (int) $cartItem['available_quantity']) {
            flash('error', $cartItem['card_name'] . ' only has ' . (int) $cartItem['available_quantity'] . ' available.');
            redirect('/cart');
        }

        $this->cartItems->updateQuantity((int) current_user()['id'], $cartItemId, (int) $quantity);
        flash('success', 'Cart updated.');
        redirect('/cart');
    }

    public function delete(): void
    {
        $this->requireLogin();
        $this->cartItems->delete((int) current_user()['id'], (int) ($_GET['id'] ?? 0));

        flash('success', 'Item removed from cart.');
        redirect('/cart');
    }

    public function clear(): void
    {
        $this->requireLogin();
        $this->cartItems->clear((int) current_user()['id']);

        flash('success', 'Cart cleared.');
        redirect('/cart');
    }

    private function requireLogin(): void
    {
        require_trade_access();
    }

    private function findPurchasableListing(): array
    {
        $listingId = (int) ($_GET['listing_id'] ?? 0);
        $listing = $listingId > 0 ? $this->listings->find($listingId) : null;

        if ($listing === null) {
            http_response_code(404);
            echo '404 - Listing not found';
            exit;
        }

        if ((int) $listing['user_id'] === (int) current_user()['id']) {
            flash('error', 'You cannot add your own listing to cart.');
            redirect('/listings');
        }

        if ($listing['status'] !== 'active' || (int) $listing['quantity'] < 1
            || ($listing['account_status'] ?? '') !== 'active' || empty($listing['email_verified_at'])) {
            flash('error', 'That listing is not currently available.');
            redirect('/listings');
        }

        return $listing;
    }

    private function summary(array $items): array
    {
        $subtotal = 0.00;
        $quantity = 0;
        $sellerIds = [];

        foreach ($items as $item) {
            $subtotal += (float) $item['price_php'] * (int) $item['quantity'];
            $quantity += (int) $item['quantity'];
            $sellerIds[(int) $item['seller_id']] = true;
        }

        return [
            'subtotal' => $subtotal,
            'quantity' => $quantity,
            'seller_count' => count($sellerIds),
        ];
    }
}
