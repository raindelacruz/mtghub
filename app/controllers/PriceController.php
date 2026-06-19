<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Card.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'PriceHistory.php';

class PriceController extends Controller
{
    private Card $cards;
    private PriceHistory $prices;

    public function __construct()
    {
        $this->cards = new Card();
        $this->prices = new PriceHistory();
    }

    public function index(): void
    {
        $this->view('prices.index', [
            'title' => 'Price Tracker',
            'prices' => $this->prices->recent(),
        ]);
    }

    public function create(): void
    {
        $this->requireAdmin();
        $card = $this->findCardFromRequest();

        $this->view('prices.form', [
            'title' => 'Add Price Entry',
            'action' => url('/prices/store?card_id=' . (int) $card['id']),
            'buttonText' => 'Save price',
            'card' => $card,
            'price' => $this->emptyPrice(),
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireAdmin();
        $card = $this->findCardFromRequest();
        $data = $this->priceDataFromRequest();
        $data['card_id'] = (int) $card['id'];
        $errors = $this->validate($data);

        if ($errors !== []) {
            $this->view('prices.form', [
                'title' => 'Add Price Entry',
                'action' => url('/prices/store?card_id=' . (int) $card['id']),
                'buttonText' => 'Save price',
                'card' => $card,
                'price' => $data,
                'errors' => $errors,
            ]);
            return;
        }

        $this->prices->create($data);
        flash('success', 'Price entry saved.');
        redirect('/cards/show?id=' . (int) $card['id']);
    }

    private function requireAdmin(): void
    {
        if (!is_admin()) {
            flash('error', 'Admin access is required for price entry.');
            redirect(is_logged_in() ? '/prices' : '/login');
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

    private function priceDataFromRequest(): array
    {
        return [
            'source_name' => trim($_POST['source_name'] ?? ''),
            'currency' => trim($_POST['currency'] ?? 'PHP'),
            'price' => trim($_POST['price'] ?? ''),
            'converted_php_price' => trim($_POST['converted_php_price'] ?? ''),
            'date_captured' => trim($_POST['date_captured'] ?? date('Y-m-d')),
            'notes' => trim($_POST['notes'] ?? ''),
        ];
    }

    private function validate(array $data): array
    {
        $errors = [];

        if (mb_strlen($data['source_name']) < 2 || mb_strlen($data['source_name']) > 150) {
            $errors[] = 'Source name must be between 2 and 150 characters.';
        }

        if (!preg_match('/^[A-Za-z]{3}$/', $data['currency'])) {
            $errors[] = 'Currency must be a three-letter code such as PHP, USD, or JPY.';
        }

        foreach (['price' => 'Price', 'converted_php_price' => 'Converted PHP price'] as $field => $label) {
            if (filter_var($data[$field], FILTER_VALIDATE_FLOAT) === false || (float) $data[$field] < 0) {
                $errors[] = $label . ' must be a valid non-negative number.';
            }
        }

        $date = DateTime::createFromFormat('Y-m-d', $data['date_captured']);
        if (!$date || $date->format('Y-m-d') !== $data['date_captured']) {
            $errors[] = 'Date captured must be a valid date.';
        }

        if (mb_strlen($data['notes']) > 1000) {
            $errors[] = 'Notes must be 1000 characters or fewer.';
        }

        return $errors;
    }

    private function emptyPrice(): array
    {
        return [
            'source_name' => '',
            'currency' => 'PHP',
            'price' => '',
            'converted_php_price' => '',
            'date_captured' => date('Y-m-d'),
            'notes' => '',
        ];
    }
}
