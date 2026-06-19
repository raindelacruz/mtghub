<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Card.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Listing.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'MtgHubBuylistEntry.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'PriceHistory.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'WishlistItem.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'ScryfallClient.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'ScryfallBulkImporter.php';

class CardController extends Controller
{
    private Card $cards;
    private Listing $listings;
    private MtgHubBuylistEntry $mtghubBuylistEntries;
    private PriceHistory $prices;
    private WishlistItem $wishlist;
    private ScryfallClient $scryfall;

    public function __construct()
    {
        $this->cards = new Card();
        $this->listings = new Listing();
        $this->mtghubBuylistEntries = new MtgHubBuylistEntry();
        $this->prices = new PriceHistory();
        $this->wishlist = new WishlistItem();
        $this->scryfall = new ScryfallClient();
    }

    public function index(): void
    {
        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'rarity' => trim($_GET['rarity'] ?? ''),
            'color' => trim($_GET['color'] ?? ''),
            'set_name' => trim($_GET['set_name'] ?? ''),
        ];
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 40;
        $totalCards = $this->cards->countSearch($filters);
        $totalPages = max(1, (int) ceil($totalCards / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $this->view('cards.index', [
            'title' => 'Cards',
            'cards' => $this->cards->search($filters, $perPage, ($page - 1) * $perPage),
            'filters' => $filters,
            'rarities' => $this->cards->distinctValues('rarity'),
            'colors' => $this->cards->distinctValues('color'),
            'sets' => $this->cards->distinctValues('set_name'),
            'pagination' => [
                'page' => $page,
                'perPage' => $perPage,
                'total' => $totalCards,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    public function show(): void
    {
        $card = $this->findCardFromRequest();

        $this->view('cards.show', [
            'title' => $card['card_name'],
            'card' => $card,
            'activeListings' => $this->listings->activeForCard((int) $card['id']),
            'wantedListDemandCount' => $this->wishlist->demandCountForCard((int) $card['id']),
            'mtghubBuylistEntry' => $this->mtghubBuylistEntries->findActiveForCard((int) $card['id']),
            'latestPrice' => $this->prices->latestForCard((int) $card['id']),
            'priceHistory' => $this->prices->forCard((int) $card['id']),
        ]);
    }

    public function create(): void
    {
        $this->requireAdmin();

        $this->view('cards.form', [
            'title' => 'Add Card',
            'action' => url('/cards/store'),
            'buttonText' => 'Add card',
            'card' => $this->emptyCard(),
            'errors' => [],
        ]);
    }

    public function import(): void
    {
        $this->requireAdmin();

        $query = trim($_GET['q'] ?? '');
        $results = [];
        $error = null;

        if ($query !== '') {
            $results = $this->scryfall->search($query);
            $error = $this->scryfall->lastError();
        }

        $this->view('cards.import', [
            'title' => 'Import from Scryfall',
            'query' => $query,
            'results' => $results,
            'error' => $error,
            'scryfall' => $this->scryfall,
        ]);
    }

    public function importStore(): void
    {
        $this->requireAdmin();

        $scryfallId = trim($_POST['scryfall_id'] ?? '');

        if ($scryfallId === '') {
            flash('error', 'Choose a Scryfall card to import.');
            redirect('/cards/import');
        }

        $scryfallCard = $this->scryfall->find($scryfallId);

        if ($scryfallCard === null) {
            flash('error', $this->scryfall->lastError() ?? 'Unable to import that Scryfall card.');
            redirect('/cards/import');
        }

        $cardData = $this->scryfall->toCardData($scryfallCard);
        $errors = $this->validate($cardData);

        if ($errors !== []) {
            flash('error', 'Scryfall returned card data that does not fit MTGHub yet: ' . implode(' ', $errors));
            redirect('/cards/import?q=' . urlencode($cardData['card_name']));
        }

        $cardId = $this->cards->upsertFromScryfall($cardData);

        flash('success', 'Imported ' . $cardData['card_name'] . ' from Scryfall.');
        redirect('/cards/show?id=' . $cardId);
    }

    public function syncScryfall(): void
    {
        $this->requireAdmin();

        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        $limit = (int) ($_POST['limit'] ?? 0);
        $importer = new ScryfallBulkImporter($this->cards, $this->scryfall);

        try {
            $stats = $importer->syncDefaultCards($limit > 0 ? $limit : null);
            flash(
                'success',
                sprintf(
                    'Scryfall sync complete. Processed %d cards, saved %d, skipped %d, failed %d.',
                    $stats['processed'],
                    $stats['saved'],
                    $stats['skipped'],
                    $stats['failed']
                )
            );
        } catch (Throwable $exception) {
            flash('error', 'Scryfall sync failed: ' . $exception->getMessage());
        }

        redirect('/cards/import');
    }

    public function store(): void
    {
        $this->requireAdmin();

        $data = $this->cardDataFromRequest();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->view('cards.form', [
                'title' => 'Add Card',
                'action' => url('/cards/store'),
                'buttonText' => 'Add card',
                'card' => $data,
                'errors' => $errors,
            ]);
            return;
        }

        $this->cards->create($data);
        flash('success', 'Card added to the database.');
        redirect('/cards');
    }

    public function edit(): void
    {
        $this->requireAdmin();
        $card = $this->findCardFromRequest();

        $this->view('cards.form', [
            'title' => 'Edit Card',
            'action' => url('/cards/update?id=' . (int) $card['id']),
            'buttonText' => 'Save changes',
            'card' => $card,
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        $this->requireAdmin();
        $card = $this->findCardFromRequest();
        $data = $this->cardDataFromRequest();
        $errors = $this->validate($data);

        if ($errors !== []) {
            $data['id'] = $card['id'];
            $this->view('cards.form', [
                'title' => 'Edit Card',
                'action' => url('/cards/update?id=' . (int) $card['id']),
                'buttonText' => 'Save changes',
                'card' => $data,
                'errors' => $errors,
            ]);
            return;
        }

        $this->cards->update((int) $card['id'], $data);
        flash('success', 'Card updated.');
        redirect('/cards/show?id=' . (int) $card['id']);
    }

    private function requireAdmin(): void
    {
        if (!is_admin()) {
            flash('error', 'Admin access is required for that action.');
            redirect(is_logged_in() ? '/cards' : '/login');
        }
    }

    private function findCardFromRequest(): array
    {
        $id = (int) ($_GET['id'] ?? 0);
        $card = $id > 0 ? $this->cards->find($id) : null;

        if ($card === null) {
            http_response_code(404);
            echo '404 - Card not found';
            exit;
        }

        return $card;
    }

    private function cardDataFromRequest(): array
    {
        return [
            'card_name' => trim($_POST['card_name'] ?? ''),
            'set_name' => trim($_POST['set_name'] ?? ''),
            'collector_number' => trim($_POST['collector_number'] ?? ''),
            'rarity' => trim($_POST['rarity'] ?? ''),
            'color' => trim($_POST['color'] ?? ''),
            'type_line' => trim($_POST['type_line'] ?? ''),
            'image_url' => trim($_POST['image_url'] ?? ''),
            'scryfall_id' => trim($_POST['scryfall_id'] ?? ''),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];
        $rarities = ['common', 'uncommon', 'rare', 'mythic'];

        if (mb_strlen($data['card_name']) < 1 || mb_strlen($data['card_name']) > 150) {
            $errors[] = 'Card name is required and must be 150 characters or fewer.';
        }

        if (mb_strlen($data['set_name']) < 1 || mb_strlen($data['set_name']) > 150) {
            $errors[] = 'Set name is required and must be 150 characters or fewer.';
        }

        if (mb_strlen($data['collector_number']) > 30) {
            $errors[] = 'Collector number must be 30 characters or fewer.';
        }

        if (!in_array($data['rarity'], $rarities, true)) {
            $errors[] = 'Choose a valid rarity.';
        }

        if (mb_strlen($data['color']) > 50) {
            $errors[] = 'Color must be 50 characters or fewer.';
        }

        if (mb_strlen($data['type_line']) < 1 || mb_strlen($data['type_line']) > 150) {
            $errors[] = 'Type is required and must be 150 characters or fewer.';
        }

        if ($data['image_url'] !== '' && !filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Image URL must be a valid URL.';
        }

        if (mb_strlen($data['scryfall_id']) > 80) {
            $errors[] = 'Scryfall ID must be 80 characters or fewer.';
        }

        return $errors;
    }

    private function emptyCard(): array
    {
        return [
            'card_name' => '',
            'set_name' => '',
            'collector_number' => '',
            'rarity' => 'common',
            'color' => '',
            'type_line' => '',
            'image_url' => '',
            'scryfall_id' => '',
        ];
    }
}
