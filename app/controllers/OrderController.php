<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'CartItem.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Order.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Wallet.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'PaymentProof.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'OrderMessage.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Dispute.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'MarketplaceReview.php';

class OrderController extends Controller
{
    private CartItem $cartItems;
    private Order $orders;
    private PaymentProof $proofs;
    private OrderMessage $messages;

    public function __construct()
    {
        $this->cartItems = new CartItem();
        $this->orders = new Order();
        $this->proofs = new PaymentProof();
        $this->messages = new OrderMessage();
    }

    public function index(): void
    {
        $this->requireLogin();
        $this->orders->expireOverdue();
        $this->orders->settleEligibleOrders();

        $this->view('orders.index', [
            'title' => 'My Orders',
            'orders' => $this->orders->forBuyer((int) current_user()['id']),
        ]);
    }

    public function sales(): void
    {
        $this->requireLogin();
        $this->orders->expireOverdue();
        $this->orders->settleEligibleOrders();

        $this->view('orders.sales', [
            'title' => 'Sales Orders',
            'orders' => $this->orders->forSeller((int) current_user()['id']),
        ]);
    }

    public function checkout(): void
    {
        $this->requireLogin();
        $items = $this->cartItems->forBuyer((int) current_user()['id']);

        $this->view('orders.checkout', [
            'title' => 'Checkout',
            'items' => $items,
            'checkout' => $this->emptyCheckout(),
            'summary' => $this->summary($items, 'meetup', 0.00),
            'walletBalance' => Wallet::getBalance((int) current_user()['id']),
            'errors' => $this->cartErrors($items),
        ]);
    }

    public function place(): void
    {
        $this->requireLogin();
        $items = $this->cartItems->forBuyer((int) current_user()['id']);
        $data = $this->checkoutDataFromRequest();
        $summary = $this->summary($items, $data['logistics_method'], $data['store_credit_to_use']);
        $walletBalance = Wallet::getBalance((int) current_user()['id']);
        $errors = array_merge($this->cartErrors($items), $this->validateCheckout($data, $summary, $walletBalance));

        if ($errors !== []) {
            $this->view('orders.checkout', [
                'title' => 'Checkout',
                'items' => $items,
                'checkout' => $data,
                'summary' => $summary,
                'walletBalance' => $walletBalance,
                'errors' => $errors,
            ]);
            return;
        }

        try {
            $orderId = $this->orders->createFromCart((int) current_user()['id'], $data);
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
            redirect('/cart');
        }

        flash('success', 'Order placed. Submit payment within 24 hours if a cash balance is due.');
        redirect('/orders/show?id=' . $orderId);
    }

    public function show(): void
    {
        $this->requireLogin();
        $this->orders->expireOverdue();
        $this->orders->settleEligibleOrders();
        $order = $this->findOrderFromRequest();
        $this->authorizeParticipant($order);
        $isBuyer = (int) $order['buyer_id'] === (int) current_user()['id'];
        $this->messages->markRead((int) $order['id'], $isBuyer);
        $this->view('orders.show', [
            'title' => 'Order #' . (int) $order['id'],
            'order' => $order,
            'items' => $this->orders->itemsForOrder((int) $order['id']),
            'history' => $this->orders->historyForOrder((int) $order['id']),
            'paymentProofs' => $this->proofs->forOrder((int) $order['id']),
            'messages' => $this->messages->forOrder((int) $order['id']),
            'disputes' => (new Dispute())->forOrder((int) $order['id']),
            'activeDispute' => (new Dispute())->activeForOrder((int) $order['id']),
            'review' => (new MarketplaceReview())->findForOrder((int) $order['id']),
            'isBuyer' => $isBuyer,
            'isSeller' => (int) $order['seller_id'] === (int) current_user()['id'],
        ]);
    }

    public function submitPayment(): void
    {
        $this->requireLogin();
        $order = $this->findOrderFromRequest();
        $buyerId = (int) current_user()['id'];
        $method = trim($_POST['external_payment_method'] ?? '');
        $reference = trim($_POST['payment_reference'] ?? '');
        if ((int) $order['buyer_id'] !== $buyerId || $order['status'] !== 'pending_payment') {
            flash('error', 'You cannot submit payment for this order.');
            redirect('/orders');
        }
        if (mb_strlen($method) < 2 || mb_strlen($method) > 80 || mb_strlen($reference) < 2 || mb_strlen($reference) > 150) {
            flash('error', 'Enter a payment method and reference using the allowed lengths.');
            redirect('/orders/show?id=' . (int) $order['id']);
        }
        $this->orders->submitPayment($order, $method, $reference, $buyerId);
        flash('success', 'Payment details submitted for seller verification.');
        redirect('/orders/show?id=' . (int) $order['id']);
    }

    public function addTracking(): void
    {
        $this->requireLogin();
        $order = $this->findOrderFromRequest();
        $sellerId = (int) current_user()['id'];
        $carrier = trim($_POST['tracking_carrier'] ?? '');
        $reference = trim($_POST['tracking_reference'] ?? '');
        if ((int) $order['seller_id'] !== $sellerId || mb_strlen($carrier) < 2 || mb_strlen($carrier) > 80
            || mb_strlen($reference) < 2 || mb_strlen($reference) > 150) {
            flash('error', 'Enter a valid carrier and tracking reference.');
            redirect('/orders/show?id=' . (int) $order['id']);
        }
        try {
            $this->orders->addTracking($order, $carrier, $reference, $sellerId);
            flash('success', 'Shipment tracking added.');
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('/orders/show?id=' . (int) $order['id']);
    }

    public function updateStatus(): void
    {
        $this->requireLogin();
        $order = $this->findOrderFromRequest();
        $status = trim($_POST['status'] ?? '');

        if (!$this->canSetStatus($order, $status)) {
            flash('error', 'You cannot make that order status change.');
            redirect('/orders/show?id=' . (int) $order['id']);
        }

        try {
            $this->orders->updateStatus($order, $status, (int) current_user()['id'], trim($_POST['note'] ?? ''));
            flash('success', 'Order status updated.');
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('/orders/show?id=' . (int) $order['id']);
    }

    private function requireLogin(): void
    {
        require_trade_access();
    }

    private function findOrderFromRequest(): array
    {
        $orderId = (int) ($_GET['id'] ?? 0);
        $order = $orderId > 0 ? $this->orders->find($orderId) : null;

        if ($order === null) {
            http_response_code(404);
            echo '404 - Order not found';
            exit;
        }

        return $order;
    }

    private function emptyCheckout(): array
    {
        return [
            'buyer_location' => current_user()['city'] . ', ' . current_user()['province'],
            'logistics_method' => 'meetup',
            'delivery_details' => 'Meetup details to be coordinated',
            'external_payment_method' => 'GCash',
            'payment_reference' => '',
            'store_credit_to_use' => '0.00',
            'notes' => '',
        ];
    }

    private function checkoutDataFromRequest(): array
    {
        return [
            'buyer_location' => trim($_POST['buyer_location'] ?? ''),
            'logistics_method' => trim($_POST['logistics_method'] ?? 'meetup'),
            'delivery_details' => trim($_POST['delivery_details'] ?? ''),
            'external_payment_method' => trim($_POST['external_payment_method'] ?? ''),
            'payment_reference' => trim($_POST['payment_reference'] ?? ''),
            'store_credit_to_use' => trim($_POST['store_credit_to_use'] ?? '0'),
            'notes' => trim($_POST['notes'] ?? ''),
        ];
    }

    private function validateCheckout(array $data, array $summary, float $walletBalance): array
    {
        $errors = [];

        if (mb_strlen($data['buyer_location']) < 2 || mb_strlen($data['buyer_location']) > 150) {
            $errors[] = 'Buyer location must be between 2 and 150 characters.';
        }

        if (!in_array($data['logistics_method'], ['meetup', 'lbc'], true)) {
            $errors[] = 'Choose meetup or LBC shipping.';
        }

        if (mb_strlen($data['delivery_details']) < 2 || mb_strlen($data['delivery_details']) > 255) {
            $errors[] = 'Delivery or meetup details must be between 2 and 255 characters.';
        }

        if (mb_strlen($data['external_payment_method']) > 80) {
            $errors[] = 'Payment method must be 80 characters or fewer.';
        }

        $storeCreditToUse = filter_var($data['store_credit_to_use'], FILTER_VALIDATE_FLOAT);

        if ($storeCreditToUse === false || $storeCreditToUse < 0) {
            $errors[] = 'Store credit to use must be zero or more.';
            $storeCreditToUse = 0.00;
        }

        if ($storeCreditToUse > $walletBalance) {
            $errors[] = 'Store credit to use cannot exceed your wallet balance.';
        }

        if ($storeCreditToUse > (float) $summary['total']) {
            $errors[] = 'Store credit to use cannot exceed the order total.';
        }

        if ((float) $summary['cash_due'] > 0 && mb_strlen($data['external_payment_method']) < 2) {
            $errors[] = 'Payment method must be between 2 and 80 characters.';
        }

        if (mb_strlen($data['payment_reference']) > 150) {
            $errors[] = 'Payment reference must be 150 characters or fewer.';
        }

        if (mb_strlen($data['notes']) > 1000) {
            $errors[] = 'Notes must be 1000 characters or fewer.';
        }

        return $errors;
    }

    private function cartErrors(array $items): array
    {
        if ($items === []) {
            return ['Your cart is empty.'];
        }

        $errors = [];
        $sellerIds = [];

        foreach ($items as $item) {
            $sellerIds[(int) $item['seller_id']] = true;

            if ($item['listing_status'] !== 'active') {
                $errors[] = $item['card_name'] . ' is no longer active.';
            }

            if ((int) $item['quantity'] > (int) $item['available_quantity']) {
                $errors[] = $item['card_name'] . ' only has ' . (int) $item['available_quantity'] . ' available.';
            }
        }

        if (count($sellerIds) > 1) {
            $errors[] = 'Checkout supports one seller at a time.';
        }

        return $errors;
    }

    private function summary(array $items, string $logisticsMethod, float|string $storeCreditToUse): array
    {
        $subtotal = 0.00;
        $quantity = 0;

        foreach ($items as $item) {
            $subtotal += (float) $item['price_php'] * (int) $item['quantity'];
            $quantity += (int) $item['quantity'];
        }

        $shipping = $this->orders->logisticsFee($logisticsMethod);
        $total = $subtotal + $shipping;
        $storeCredit = max(0.00, (float) $storeCreditToUse);

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total' => $total,
            'store_credit_to_use' => min($storeCredit, $total),
            'cash_due' => max(0.00, $total - $storeCredit),
            'quantity' => $quantity,
        ];
    }

    private function canSetStatus(array $order, string $status): bool
    {
        $userId = (int) current_user()['id'];

        if ((int) $order['seller_id'] === $userId) {
            return match ($order['status']) {
                'pending_payment' => $status === 'cancelled',
                'payment_verified' => $status === ($order['logistics_method'] === 'lbc' ? 'preparing' : 'ready_for_meetup'),
                'buyer_confirmed' => $status === 'completed',
                default => false,
            };
        }

        if ((int) $order['buyer_id'] === $userId) {
            return match ($order['status']) {
                'pending_payment' => $status === 'cancelled',
                'shipped', 'ready_for_meetup' => $status === 'delivered',
                'delivered' => $status === 'buyer_confirmed',
                default => false,
            };
        }

        return false;
    }

    private function authorizeParticipant(array $order): void
    {
        $userId = (int) current_user()['id'];
        if ((int) $order['buyer_id'] !== $userId && (int) $order['seller_id'] !== $userId) {
            http_response_code(404);
            echo '404 - Order not found';
            exit;
        }
    }

    private function orderReturnPath(array $order): string
    {
        return (int) $order['seller_id'] === (int) current_user()['id'] ? '/orders/sales' : '/orders';
    }
}
