<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Card.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'BuylistOffer.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'WishlistItem.php';

class WishlistController extends Controller
{
    private Card $cards;
    private BuylistOffer $offers;
    private WishlistItem $wishlist;

    public function __construct()
    {
        $this->cards = new Card();
        $this->offers = new BuylistOffer();
        $this->wishlist = new WishlistItem();
    }

    public function index(): void
    {
        $this->requireLogin();
        $userId = (int) current_user()['id'];

        $this->view('wishlist.index', [
            'title' => 'My Wanted List',
            'items' => $this->wishlist->forUser($userId),
            'matches' => $this->wishlist->matchesForUser($userId),
            'offers' => $this->offers->forBuyer($userId),
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $card = $this->findCardFromRequest();

        $this->view('wishlist.form', [
            'title' => 'Add to Wanted List',
            'action' => url('/buylist/store?card_id=' . (int) $card['id']),
            'buttonText' => 'Add to wanted list',
            'card' => $card,
            'item' => $this->emptyItem(),
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $card = $this->findCardFromRequest();
        $userId = (int) current_user()['id'];

        if ($this->wishlist->existsForUserAndCard($userId, (int) $card['id'])) {
            flash('error', 'That card is already on your wanted list.');
            redirect('/buylist');
        }

        $data = $this->itemDataFromRequest();
        $data['user_id'] = $userId;
        $data['card_id'] = (int) $card['id'];
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->view('wishlist.form', [
                'title' => 'Add to Wanted List',
                'action' => url('/buylist/store?card_id=' . (int) $card['id']),
                'buttonText' => 'Add to wanted list',
                'card' => $card,
                'item' => $data,
                'errors' => $errors,
            ]);
            return;
        }

        $this->wishlist->create($data);
        flash('success', 'Card added to your wanted list.');
        redirect('/buylist');
    }

    public function edit(): void
    {
        $this->requireLogin();
        $item = $this->findItemFromRequest();

        $this->view('wishlist.form', [
            'title' => 'Edit Wanted List Item',
            'action' => url('/buylist/update?id=' . (int) $item['id']),
            'buttonText' => 'Save changes',
            'card' => $item,
            'item' => $item,
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();
        $item = $this->findItemFromRequest();
        $data = $this->itemDataFromRequest();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->view('wishlist.form', [
                'title' => 'Edit Wanted List Item',
                'action' => url('/buylist/update?id=' . (int) $item['id']),
                'buttonText' => 'Save changes',
                'card' => $item,
                'item' => array_merge($item, $data),
                'errors' => $errors,
            ]);
            return;
        }

        $this->wishlist->update((int) $item['id'], (int) current_user()['id'], $data);
        flash('success', 'Wanted list item updated.');
        redirect('/buylist');
    }

    public function delete(): void
    {
        $this->requireLogin();
        $item = $this->findItemFromRequest();
        $this->wishlist->delete((int) $item['id'], (int) current_user()['id']);

        flash('success', 'Wanted list item removed.');
        redirect('/buylist');
    }

    private function requireLogin(): void
    {
        if (!is_logged_in()) {
            flash('error', 'Please log in to manage your wanted list.');
            redirect('/login');
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

    private function findItemFromRequest(): array
    {
        $id = (int) ($_GET['id'] ?? 0);
        $item = $id > 0 ? $this->wishlist->findForUser($id, (int) current_user()['id']) : null;

        if ($item === null) {
            http_response_code(404);
            echo '404 - Wanted list item not found';
            exit;
        }

        return $item;
    }

    private function itemDataFromRequest(): array
    {
        return [
            'desired_quantity' => trim($_POST['desired_quantity'] ?? '1'),
            'max_price_php' => trim($_POST['max_price_php'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if (filter_var($data['desired_quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors[] = 'Desired quantity must be at least 1.';
        }

        if ($data['max_price_php'] !== '' && (filter_var($data['max_price_php'], FILTER_VALIDATE_FLOAT) === false || (float) $data['max_price_php'] < 0)) {
            $errors[] = 'Max price must be a valid non-negative number.';
        }

        if (mb_strlen($data['notes']) > 1000) {
            $errors[] = 'Notes must be 1000 characters or fewer.';
        }

        return $errors;
    }

    private function emptyItem(): array
    {
        return [
            'desired_quantity' => 1,
            'max_price_php' => '',
            'notes' => '',
        ];
    }
}
