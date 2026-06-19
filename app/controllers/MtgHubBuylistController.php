<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'MtgHubBuylistEntry.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'MtgHubBuylistOrder.php';

class MtgHubBuylistController extends Controller
{
    private MtgHubBuylistEntry $entries;
    private MtgHubBuylistOrder $orders;

    public function __construct()
    {
        $this->entries = new MtgHubBuylistEntry();
        $this->orders = new MtgHubBuylistOrder();
    }

    public function index(): void
    {
        $this->requireLogin();

        $this->view('mtghub_buylist.index', [
            'title' => 'Sell to MTGHub',
            'entries' => $this->entries->allActive(),
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();
        $entry = $this->findEntryFromRequest();

        if ((int) $entry['is_active'] !== 1 || (int) $entry['remaining_quantity'] < 1) {
            flash('error', 'That MTGHub buylist entry is no longer accepting submissions.');
            redirect('/sell-to-mtghub');
        }

        $this->view('mtghub_buylist.create', [
            'title' => 'Create Sell Order',
            'entry' => $entry,
            'order' => $this->emptyOrder(),
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();
        $entry = $this->findEntryFromRequest();
        $data = $this->orderDataFromRequest();
        $errors = $this->validateOrder($data, (int) $entry['remaining_quantity']);

        if ($errors !== []) {
            $this->view('mtghub_buylist.create', [
                'title' => 'Create Sell Order',
                'entry' => $entry,
                'order' => $data,
                'errors' => $errors,
            ]);
            return;
        }

        try {
            $orderId = $this->orders->createOrder(
                (int) current_user()['id'],
                (int) $entry['id'],
                (int) $data['quantity'],
                $data['declared_condition'],
                $data['payout_method'],
                $data['remarks']
            );
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
            redirect('/sell-to-mtghub/create?buylist_id=' . (int) $entry['id']);
        }

        flash('success', 'Sell order submitted to MTGHub.');
        redirect('/my-sell-orders/view?id=' . $orderId);
    }

    public function myOrders(): void
    {
        $this->requireLogin();

        $this->view('mtghub_buylist.my_orders', [
            'title' => 'My Sell Orders',
            'orders' => $this->orders->listForUser((int) current_user()['id']),
        ]);
    }

    public function showMyOrder(): void
    {
        $this->requireLogin();
        $id = (int) ($_GET['id'] ?? 0);
        $order = $id > 0 ? $this->orders->findForUser($id, (int) current_user()['id']) : null;

        if ($order === null) {
            http_response_code(404);
            echo '404 - Sell order not found';
            exit;
        }

        $this->view('mtghub_buylist.show', [
            'title' => 'Sell Order #' . (int) $order['id'],
            'order' => $order,
        ]);
    }

    private function requireLogin(): void
    {
        require_trade_access();
    }

    private function findEntryFromRequest(): array
    {
        $id = (int) ($_GET['buylist_id'] ?? $_GET['id'] ?? 0);
        $entry = $id > 0 ? $this->entries->find($id) : null;

        if ($entry === null) {
            http_response_code(404);
            echo '404 - MTGHub buylist entry not found';
            exit;
        }

        return $entry;
    }

    private function orderDataFromRequest(): array
    {
        return [
            'quantity' => trim($_POST['quantity'] ?? '1'),
            'declared_condition' => trim($_POST['declared_condition'] ?? ''),
            'payout_method' => trim($_POST['payout_method'] ?? 'cash'),
            'remarks' => trim($_POST['remarks'] ?? ''),
        ];
    }

    private function validateOrder(array $data, int $remainingQuantity): array
    {
        $errors = [];
        $conditions = ['near_mint', 'lightly_played', 'moderately_played', 'heavily_played', 'damaged'];

        if (filter_var($data['quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors[] = 'Quantity must be at least 1.';
        } elseif ((int) $data['quantity'] > $remainingQuantity) {
            $errors[] = 'Quantity cannot exceed the remaining MTGHub target.';
        }

        if (!in_array($data['declared_condition'], $conditions, true)) {
            $errors[] = 'Choose a valid declared condition.';
        }

        if (!in_array($data['payout_method'], ['cash', 'store_credit'], true)) {
            $errors[] = 'Choose cash or store credit payout.';
        }

        if (mb_strlen($data['remarks']) > 1000) {
            $errors[] = 'Remarks must be 1000 characters or fewer.';
        }

        return $errors;
    }

    private function emptyOrder(): array
    {
        return [
            'quantity' => 1,
            'declared_condition' => 'near_mint',
            'payout_method' => 'cash',
            'remarks' => '',
        ];
    }
}
