<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Card.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'MtgHubBuylistEntry.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'MtgHubBuylistOrder.php';

class AdminMtgHubBuylistController extends Controller
{
    private Card $cards;
    private MtgHubBuylistEntry $entries;
    private MtgHubBuylistOrder $orders;

    public function __construct()
    {
        $this->cards = new Card();
        $this->entries = new MtgHubBuylistEntry();
        $this->orders = new MtgHubBuylistOrder();
    }

    public function index(): void
    {
        $this->requireAdmin();

        $this->view('admin.mtghub_buylist.index', [
            'title' => 'MTGHub Buylist',
            'entries' => $this->entries->allForAdmin(),
        ]);
    }

    public function create(): void
    {
        $this->requireAdmin();

        $this->view('admin.mtghub_buylist.create', [
            'title' => 'Add MTGHub Buylist Entry',
            'selectedCard' => null,
            'cardSearchUrl' => url('/admin/mtghub-buylist/cards'),
            'entry' => $this->emptyEntry(),
            'action' => url('/admin/mtghub-buylist/store'),
            'buttonText' => 'Add entry',
            'errors' => [],
        ]);
    }

    public function store(): void
    {
        $this->requireAdmin();
        $data = $this->entryDataFromRequest();
        $errors = $this->validateEntry($data, true);

        if ($errors !== []) {
            $this->view('admin.mtghub_buylist.create', [
                'title' => 'Add MTGHub Buylist Entry',
                'selectedCard' => $data['card_id'] > 0 ? $this->cards->find((int) $data['card_id']) : null,
                'cardSearchUrl' => url('/admin/mtghub-buylist/cards'),
                'entry' => $data,
                'action' => url('/admin/mtghub-buylist/store'),
                'buttonText' => 'Add entry',
                'errors' => $errors,
            ]);
            return;
        }

        $this->entries->create($data);
        flash('success', 'MTGHub buylist entry added.');
        redirect('/admin/mtghub-buylist');
    }

    public function edit(): void
    {
        $this->requireAdmin();
        $entry = $this->findEntryFromRequest();

        $this->view('admin.mtghub_buylist.edit', [
            'title' => 'Edit MTGHub Buylist Entry',
            'entry' => $entry,
            'action' => url('/admin/mtghub-buylist/update?id=' . (int) $entry['id']),
            'buttonText' => 'Save changes',
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        $this->requireAdmin();
        $entry = $this->findEntryFromRequest();
        $data = $this->entryDataFromRequest();
        $data['card_id'] = (int) $entry['card_id'];
        $errors = $this->validateEntry($data, false);

        if ($errors !== []) {
            $this->view('admin.mtghub_buylist.edit', [
                'title' => 'Edit MTGHub Buylist Entry',
                'entry' => array_merge($entry, $data),
                'action' => url('/admin/mtghub-buylist/update?id=' . (int) $entry['id']),
                'buttonText' => 'Save changes',
                'errors' => $errors,
            ]);
            return;
        }

        $this->entries->update((int) $entry['id'], $data);
        flash('success', 'MTGHub buylist entry updated.');
        redirect('/admin/mtghub-buylist');
    }

    public function toggle(): void
    {
        $this->requireAdmin();
        $entry = $this->findEntryFromRequest();
        $this->entries->toggleActive((int) $entry['id']);
        flash('success', 'MTGHub buylist entry status changed.');
        redirect('/admin/mtghub-buylist');
    }

    public function searchCards(): void
    {
        $this->requireAdmin();

        $query = trim((string) ($_GET['q'] ?? ''));
        $cards = mb_strlen($query) >= 2 ? $this->cards->searchForPicker($query, 25) : [];

        header('Content-Type: application/json');
        echo json_encode(array_map(function (array $card): array {
            return [
                'id' => (int) $card['id'],
                'label' => $this->cardOptionLabel($card),
            ];
        }, $cards));
    }

    public function orders(): void
    {
        $this->requireAdmin();

        $this->view('admin.mtghub_buylist.orders', [
            'title' => 'MTGHub Buylist Orders',
            'orders' => $this->orders->listForAdmin(),
        ]);
    }

    public function showOrder(): void
    {
        $this->requireAdmin();
        $this->viewOrder();
    }

    public function markReceived(): void
    {
        $this->requireAdmin();
        $this->orders->markReceived((int) ($_GET['id'] ?? 0));
        flash('success', 'Sell order marked received.');
        redirect('/admin/mtghub-buylist/orders/view?id=' . (int) ($_GET['id'] ?? 0));
    }

    public function inspect(): void
    {
        $this->requireAdmin();
        $this->orders->markUnderInspection((int) ($_GET['id'] ?? 0));
        flash('success', 'Sell order moved to inspection.');
        redirect('/admin/mtghub-buylist/orders/view?id=' . (int) ($_GET['id'] ?? 0));
    }

    public function approve(): void
    {
        $this->requireAdmin();
        $id = (int) ($_GET['id'] ?? 0);
        $items = $_POST['items'] ?? [];
        $adminRemarks = trim($_POST['admin_remarks'] ?? '');

        try {
            $this->orders->inspectAndApprove($id, is_array($items) ? $items : [], $adminRemarks);
            $order = $this->orders->findForAdmin($id);
            if ($order && $order['payout_method'] === 'store_credit' && in_array($order['status'], ['accepted', 'partially_accepted'], true)) {
                $this->orders->creditStoreCreditIfApproved($id);
            }
            flash('success', 'Sell order approved.');
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
        }

        redirect('/admin/mtghub-buylist/orders/view?id=' . $id);
    }

    public function reject(): void
    {
        $this->requireAdmin();
        $id = (int) ($_GET['id'] ?? 0);
        $this->orders->reject($id, trim($_POST['admin_remarks'] ?? ''));
        flash('success', 'Sell order rejected.');
        redirect('/admin/mtghub-buylist/orders/view?id=' . $id);
    }

    public function completeCash(): void
    {
        $this->requireAdmin();
        $id = (int) ($_GET['id'] ?? 0);

        try {
            $this->orders->completeCashPayout($id, trim($_POST['admin_remarks'] ?? ''));
            flash('success', 'Cash payout marked completed.');
        } catch (Throwable $exception) {
            flash('error', $exception->getMessage());
        }

        redirect('/admin/mtghub-buylist/orders/view?id=' . $id);
    }

    private function requireAdmin(): void
    {
        if (!is_admin()) {
            flash('error', 'Admin access is required.');
            redirect(is_logged_in() ? '/' : '/login');
        }
    }

    private function findEntryFromRequest(): array
    {
        $id = (int) ($_GET['id'] ?? 0);
        $entry = $id > 0 ? $this->entries->find($id) : null;

        if ($entry === null) {
            http_response_code(404);
            echo '404 - MTGHub buylist entry not found';
            exit;
        }

        return $entry;
    }

    private function viewOrder(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $order = $id > 0 ? $this->orders->findForAdmin($id) : null;

        if ($order === null) {
            http_response_code(404);
            echo '404 - MTGHub buylist order not found';
            exit;
        }

        $this->view('admin.mtghub_buylist.show_order', [
            'title' => 'MTGHub Buylist Order #' . (int) $order['id'],
            'order' => $order,
        ]);
    }

    private function entryDataFromRequest(): array
    {
        return [
            'card_id' => (int) ($_POST['card_id'] ?? 0),
            'set_name' => trim($_POST['set_name'] ?? ''),
            'accepted_condition' => trim($_POST['accepted_condition'] ?? ''),
            'cash_offer' => trim($_POST['cash_offer'] ?? '0'),
            'credit_offer' => trim($_POST['credit_offer'] ?? '0'),
            'target_quantity' => trim($_POST['target_quantity'] ?? '0'),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'admin_notes' => trim($_POST['admin_notes'] ?? ''),
        ];
    }

    private function validateEntry(array $data, bool $requireCard): array
    {
        $errors = [];

        if ($requireCard && ((int) $data['card_id'] < 1 || $this->cards->find((int) $data['card_id']) === null)) {
            $errors[] = 'Choose a valid card.';
        }

        foreach (['cash_offer' => 'Cash offer', 'credit_offer' => 'Store credit offer'] as $field => $label) {
            if (filter_var($data[$field], FILTER_VALIDATE_FLOAT) === false || (float) $data[$field] < 0) {
                $errors[] = $label . ' must be a non-negative amount.';
            }
        }

        if (filter_var($data['target_quantity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors[] = 'Target quantity must be at least 1.';
        }

        if (mb_strlen($data['set_name']) > 255 || mb_strlen($data['accepted_condition']) > 50 || mb_strlen($data['admin_notes']) > 2000) {
            $errors[] = 'One or more text fields is too long.';
        }

        return $errors;
    }

    private function emptyEntry(): array
    {
        return [
            'card_id' => 0,
            'set_name' => '',
            'accepted_condition' => 'near_mint',
            'cash_offer' => '0.00',
            'credit_offer' => '0.00',
            'target_quantity' => 1,
            'is_active' => 1,
            'admin_notes' => '',
        ];
    }

    private function cardOptionLabel(array $card): string
    {
        return trim((string) $card['card_name']) . ' - ' . trim((string) $card['set_name']) . ' #' . trim((string) $card['collector_number']);
    }
}
