<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Card.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'CollectionItem.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'MtgHubBuylistEntry.php';

class CollectionController extends Controller
{
    private CollectionItem $items;
    private Card $cards;
    private MtgHubBuylistEntry $mtghubBuylistEntries;

    public function __construct()
    {
        $this->items = new CollectionItem();
        $this->cards = new Card();
        $this->mtghubBuylistEntries = new MtgHubBuylistEntry();
    }

    public function index(): void
    {
        $this->requireLogin();
        $userId = (int) current_user()['id'];

        $items = $this->items->forUser($userId);

        $this->view('collections.index', [
            'title' => 'My Collection',
            'items' => $items,
            'totals' => $this->items->totalsForUser($userId),
            'mtghubBuylistEntriesByCard' => $this->mtghubBuylistEntries->activeByCardIds(array_column($items, 'card_id')),
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $card = $this->findCardFromRequest();

        $this->view('collections.form', [
            'title' => 'Add to Collection',
            'action' => url('/collection/store?card_id=' . (int) $card['id']),
            'buttonText' => 'Add to collection',
            'card' => $card,
            'item' => $this->emptyItem(),
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $card = $this->findCardFromRequest();
        $data = $this->itemDataFromRequest();
        $data['user_id'] = (int) current_user()['id'];
        $data['card_id'] = (int) $card['id'];
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->view('collections.form', [
                'title' => 'Add to Collection',
                'action' => url('/collection/store?card_id=' . (int) $card['id']),
                'buttonText' => 'Add to collection',
                'card' => $card,
                'item' => $data,
                'errors' => $errors,
            ]);
            return;
        }

        $this->items->create($data);
        flash('success', 'Card added to your collection.');
        redirect('/collection');
    }

    public function edit(): void
    {
        $this->requireLogin();
        $item = $this->findItemFromRequest();

        $this->view('collections.form', [
            'title' => 'Edit Collection Item',
            'action' => url('/collection/update?id=' . (int) $item['id']),
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
            $this->view('collections.form', [
                'title' => 'Edit Collection Item',
                'action' => url('/collection/update?id=' . (int) $item['id']),
                'buttonText' => 'Save changes',
                'card' => $item,
                'item' => array_merge($item, $data),
                'errors' => $errors,
            ]);
            return;
        }

        $this->items->update((int) $item['id'], (int) current_user()['id'], $data);
        flash('success', 'Collection item updated.');
        redirect('/collection');
    }

    public function delete(): void
    {
        $this->requireLogin();
        $item = $this->findItemFromRequest();
        $this->items->delete((int) $item['id'], (int) current_user()['id']);

        flash('success', 'Collection item removed.');
        redirect('/collection');
    }

    private function requireLogin(): void
    {
        if (!is_logged_in()) {
            flash('error', 'Please log in to manage your collection.');
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
        $item = $id > 0 ? $this->items->findForUser($id, (int) current_user()['id']) : null;

        if ($item === null) {
            http_response_code(404);
            echo '404 - Collection item not found';
            exit;
        }

        return $item;
    }

    private function itemDataFromRequest(): array
    {
        return [
            'quantity' => trim($_POST['quantity'] ?? '1'),
            'card_condition' => trim($_POST['card_condition'] ?? 'near_mint'),
            'language' => trim($_POST['language'] ?? 'English'),
            'is_foil' => isset($_POST['is_foil']) ? 1 : 0,
            'acquisition_price' => trim($_POST['acquisition_price'] ?? '0'),
            'notes' => trim($_POST['notes'] ?? ''),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        $conditions = ['near_mint', 'lightly_played', 'moderately_played', 'heavily_played', 'damaged'];

        if (filter_var($data['quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors[] = 'Quantity must be at least 1.';
        }

        if (!in_array($data['card_condition'], $conditions, true)) {
            $errors[] = 'Choose a valid card condition.';
        }

        if (mb_strlen($data['language']) < 2 || mb_strlen($data['language']) > 50) {
            $errors[] = 'Language must be between 2 and 50 characters.';
        }

        if ($data['acquisition_price'] !== '' && filter_var($data['acquisition_price'], FILTER_VALIDATE_FLOAT) === false) {
            $errors[] = 'Acquisition price must be a valid number.';
        }

        if ((float) ($data['acquisition_price'] ?: 0) < 0) {
            $errors[] = 'Acquisition price cannot be negative.';
        }

        if (mb_strlen($data['notes']) > 1000) {
            $errors[] = 'Notes must be 1000 characters or fewer.';
        }

        return $errors;
    }

    private function emptyItem(): array
    {
        return [
            'quantity' => 1,
            'card_condition' => 'near_mint',
            'language' => 'English',
            'is_foil' => 0,
            'acquisition_price' => '0.00',
            'notes' => '',
        ];
    }
}
