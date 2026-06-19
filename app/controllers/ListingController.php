<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Card.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Listing.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'WishlistItem.php';

class ListingController extends Controller
{
    private Card $cards;
    private Listing $listings;
    private WishlistItem $buylist;

    public function __construct()
    {
        $this->cards = new Card();
        $this->listings = new Listing();
        $this->buylist = new WishlistItem();
    }

    public function index(): void
    {
        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'exclude_user_id' => is_logged_in() ? (int) current_user()['id'] : null,
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $totalListings = $this->listings->countSearch($filters);
        $totalPages = max(1, (int) ceil($totalListings / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $this->view('listings.index', [
            'title' => 'Marketplace',
            'listings' => $this->listings->search($filters, $perPage, ($page - 1) * $perPage),
            'filters' => $filters,
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalListings,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    public function mine(): void
    {
        $this->requireLogin();

        $this->view('listings.mine', [
            'title' => 'My Listings',
            'listings' => $this->listings->forUser((int) current_user()['id']),
            'buylistDemand' => $this->buylist->demandForSeller((int) current_user()['id']),
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $card = $this->findCardFromRequest();

        $this->view('listings.form', [
            'title' => 'Create Listing',
            'action' => url('/listings/store?card_id=' . (int) $card['id']),
            'buttonText' => 'Create listing',
            'card' => $card,
            'listing' => $this->emptyListing(),
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $card = $this->findCardFromRequest();
        $data = $this->listingDataFromRequest();
        $data['user_id'] = (int) current_user()['id'];
        $data['card_id'] = (int) $card['id'];
        $data['status'] = 'active';
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->view('listings.form', [
                'title' => 'Create Listing',
                'action' => url('/listings/store?card_id=' . (int) $card['id']),
                'buttonText' => 'Create listing',
                'card' => $card,
                'listing' => $data,
                'errors' => $errors,
            ]);
            return;
        }

        $this->listings->create($data);
        flash('success', 'Marketplace listing created.');
        redirect('/listings/mine');
    }

    public function edit(): void
    {
        $this->requireLogin();
        $listing = $this->findListingFromRequest();
        $this->authorizeListing($listing);

        $this->view('listings.form', [
            'title' => 'Edit Listing',
            'action' => url('/listings/update?id=' . (int) $listing['id']),
            'buttonText' => 'Save changes',
            'card' => $listing,
            'listing' => $listing,
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();
        $listing = $this->findListingFromRequest();
        $this->authorizeListing($listing);
        $data = $this->listingDataFromRequest();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->view('listings.form', [
                'title' => 'Edit Listing',
                'action' => url('/listings/update?id=' . (int) $listing['id']),
                'buttonText' => 'Save changes',
                'card' => $listing,
                'listing' => array_merge($listing, $data),
                'errors' => $errors,
            ]);
            return;
        }

        $this->listings->update((int) $listing['id'], $data);
        flash('success', 'Listing updated.');
        redirect('/listings/mine');
    }

    private function requireLogin(): void
    {
        require_trade_access();
    }

    private function authorizeListing(array $listing): void
    {
        if (!is_admin() && (int) $listing['user_id'] !== (int) current_user()['id']) {
            flash('error', 'You can only edit your own listings.');
            redirect('/listings');
        }
    }

    private function findCardFromRequest(): array
    {
        $cardId = (int) ($_GET['card_id'] ?? 0);
        $card = $cardId > 0 ? $this->cards->find($cardId) : null;

        if ($card === null) {
            http_response_code(404);
            echo '404 - Card not found';
            exit;
        }

        return $card;
    }

    private function findListingFromRequest(): array
    {
        $id = (int) ($_GET['id'] ?? 0);
        $listing = $id > 0 ? $this->listings->find($id) : null;

        if ($listing === null) {
            http_response_code(404);
            echo '404 - Listing not found';
            exit;
        }

        return $listing;
    }

    private function listingDataFromRequest(): array
    {
        return [
            'quantity' => trim($_POST['quantity'] ?? '1'),
            'card_condition' => trim($_POST['card_condition'] ?? 'near_mint'),
            'price_php' => trim($_POST['price_php'] ?? ''),
            'seller_location' => trim($_POST['seller_location'] ?? ''),
            'delivery_options' => trim($_POST['delivery_options'] ?? ''),
            'status' => trim($_POST['status'] ?? 'active'),
            'notes' => trim($_POST['notes'] ?? ''),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        $conditions = ['near_mint', 'lightly_played', 'moderately_played', 'heavily_played', 'damaged'];
        $statuses = ['active', 'reserved', 'sold', 'cancelled'];

        if (filter_var($data['quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors[] = 'Quantity must be at least 1.';
        }

        if (!in_array($data['card_condition'], $conditions, true)) {
            $errors[] = 'Choose a valid card condition.';
        }

        if (filter_var($data['price_php'], FILTER_VALIDATE_FLOAT) === false || (float) $data['price_php'] < 0) {
            $errors[] = 'Price PHP must be a valid non-negative number.';
        }

        if (mb_strlen($data['seller_location']) < 2 || mb_strlen($data['seller_location']) > 150) {
            $errors[] = 'Seller location must be between 2 and 150 characters.';
        }

        if (mb_strlen($data['delivery_options']) < 2 || mb_strlen($data['delivery_options']) > 255) {
            $errors[] = 'Delivery options must be between 2 and 255 characters.';
        }

        if (!in_array($data['status'], $statuses, true)) {
            $errors[] = 'Choose a valid listing status.';
        }

        if (mb_strlen($data['notes']) > 1000) {
            $errors[] = 'Notes must be 1000 characters or fewer.';
        }

        return $errors;
    }

    private function emptyListing(): array
    {
        return [
            'quantity' => 1,
            'card_condition' => 'near_mint',
            'price_php' => '',
            'seller_location' => current_user()['city'] . ', ' . current_user()['province'],
            'delivery_options' => 'Meetup / local courier',
            'status' => 'active',
            'notes' => '',
        ];
    }
}
