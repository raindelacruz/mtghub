<?php

require_once APP_PATH . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'NotificationService.php';

class Order
{
    public const LBC_FEE_PHP = 100.00;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function forBuyer(int $buyerId): array
    {
        $sql = $this->baseSelect() . '
                WHERE orders.buyer_id = :buyer_id
                ORDER BY orders.created_at DESC';

        $statement = $this->db->prepare($sql);
        $statement->execute(['buyer_id' => $buyerId]);

        return $statement->fetchAll();
    }

    public function forSeller(int $sellerId): array
    {
        $sql = $this->baseSelect() . '
                WHERE orders.seller_id = :seller_id
                ORDER BY orders.created_at DESC';

        $statement = $this->db->prepare($sql);
        $statement->execute(['seller_id' => $sellerId]);

        return $statement->fetchAll();
    }

    public function allForAdmin(): array
    {
        $sql = $this->baseSelect() . '
                ORDER BY orders.created_at DESC';

        return $this->db->query($sql)->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = $this->baseSelect() . '
                WHERE orders.id = :id
                LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute(['id' => $id]);
        $order = $statement->fetch();

        return $order ?: null;
    }

    public function itemsForOrder(int $orderId): array
    {
        $sql = 'SELECT order_items.*, cards.card_name, cards.set_name, cards.collector_number,
                       listings.card_condition
                FROM order_items
                INNER JOIN cards ON cards.id = order_items.card_id
                INNER JOIN listings ON listings.id = order_items.listing_id
                WHERE order_items.order_id = :order_id
                ORDER BY order_items.id ASC';

        $statement = $this->db->prepare($sql);
        $statement->execute(['order_id' => $orderId]);

        return $statement->fetchAll();
    }

    public function historyForOrder(int $orderId): array
    {
        $statement = $this->db->prepare(
            'SELECT order_status_history.*, users.username AS changed_by_username
             FROM order_status_history
             LEFT JOIN users ON users.id = order_status_history.changed_by
             WHERE order_status_history.order_id = :order_id
             ORDER BY order_status_history.created_at ASC, order_status_history.id ASC'
        );
        $statement->execute(['order_id' => $orderId]);
        return $statement->fetchAll();
    }

    public function createFromCart(int $buyerId, array $data): int
    {
        $this->db->beginTransaction();

        try {
            $cartItems = $this->lockedCartItems($buyerId);
            $this->assertCartCanCheckout($cartItems);

            $sellerId = (int) $cartItems[0]['seller_id'];
            $subtotal = $this->subtotal($cartItems);
            $logisticsFee = $this->logisticsFee($data['logistics_method']);
            $total = $subtotal + $logisticsFee;
            $storeCreditUsed = min(max((float) ($data['store_credit_to_use'] ?? 0), 0.00), $total);
            $walletBalance = Wallet::getBalance($buyerId);

            if ($storeCreditUsed > $walletBalance) {
                throw new RuntimeException('Store credit to use cannot exceed your wallet balance.');
            }

            $cashAmountDue = max(0.00, $total - $storeCreditUsed);
            $paymentMethod = $this->walletPaymentMethod($storeCreditUsed, $cashAmountDue);
            $firstItem = $cartItems[0];

            $statement = $this->db->prepare('INSERT INTO orders
                    (listing_id, buyer_id, seller_id, quantity, unit_price_php, total_price_php,
                     buyer_location, delivery_preference, logistics_method, logistics_fee_php,
                     payment_method, external_payment_method, store_credit_used, cash_amount_due,
                     payment_reference, payment_deadline, payment_status, fulfillment_status, status, notes)
                VALUES
                    (:listing_id, :buyer_id, :seller_id, :quantity, :unit_price_php, :total_price_php,
                     :buyer_location, :delivery_preference, :logistics_method, :logistics_fee_php,
                     :payment_method, :external_payment_method, :store_credit_used, :cash_amount_due,
                     :payment_reference, IF(:needs_deadline = 1, DATE_ADD(NOW(), INTERVAL 24 HOUR), NULL),
                     :payment_status, :fulfillment_status, :status, :notes)');

            $initialStatus = $cashAmountDue > 0 ? 'pending_payment' : 'payment_verified';

            $statement->execute([
                'listing_id' => (int) $firstItem['listing_id'],
                'buyer_id' => $buyerId,
                'seller_id' => $sellerId,
                'quantity' => $this->totalQuantity($cartItems),
                'unit_price_php' => number_format($subtotal, 2, '.', ''),
                'total_price_php' => number_format($total, 2, '.', ''),
                'buyer_location' => $data['buyer_location'],
                'delivery_preference' => $data['delivery_details'],
                'logistics_method' => $data['logistics_method'],
                'logistics_fee_php' => number_format($logisticsFee, 2, '.', ''),
                'payment_method' => $paymentMethod,
                'external_payment_method' => $data['external_payment_method'],
                'store_credit_used' => number_format($storeCreditUsed, 2, '.', ''),
                'cash_amount_due' => number_format($cashAmountDue, 2, '.', ''),
                'payment_reference' => '',
                'needs_deadline' => $cashAmountDue > 0 ? 1 : 0,
                'payment_status' => $cashAmountDue > 0 ? 'pending' : 'verified',
                'fulfillment_status' => $cashAmountDue > 0 ? 'awaiting_payment' : 'awaiting_fulfillment',
                'status' => $initialStatus,
                'notes' => $data['notes'] ?: null,
            ]);

            $orderId = (int) $this->db->lastInsertId();
            $this->addHistory($orderId, null, $initialStatus, $buyerId, $initialStatus === 'pending_payment' ? 'Order placed; payment due within 24 hours.' : 'Order paid in full with store credit.');

            foreach ($cartItems as $item) {
                $this->createOrderItem($orderId, $item);
                $remainingQuantity = (int) $item['available_quantity'] - (int) $item['quantity'];
                $listingStatus = $remainingQuantity === 0 ? 'reserved' : 'active';
                $this->setListingAvailability((int) $item['listing_id'], $remainingQuantity, $listingStatus);
            }

            if ($storeCreditUsed > 0) {
                Wallet::debit(
                    $buyerId,
                    $storeCreditUsed,
                    'debit_checkout_payment',
                    'order',
                    $orderId,
                    'Store credit used for order #' . $orderId,
                    null,
                    'order:' . $orderId . ':buyer-debit'
                );
            }

            $this->clearCart($buyerId);
            $this->db->commit();

            $this->notifySafely($sellerId, 'order_created', 'New order #' . $orderId, 'A buyer placed a new order.', '/orders/show?id=' . $orderId);

            return $orderId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateStatus(array $order, string $status, ?int $changedBy = null, string $note = ''): void
    {
        $this->db->beginTransaction();

        try {
            $lockedOrder = $this->lockOrder((int) $order['id']);
            if ($lockedOrder === null || $lockedOrder['status'] !== $order['status']) {
                throw new RuntimeException('This order changed before the action could be completed. Refresh and try again.');
            }
            $order = array_merge($order, $lockedOrder);

            if (in_array($status, ['cancelled', 'expired'], true) && !in_array($order['status'], ['cancelled', 'expired'], true)) {
                foreach ($this->itemsForOrder((int) $order['id']) as $item) {
                    $this->restoreListingQuantity((int) $item['listing_id'], (int) $item['quantity']);
                }

                if ((float) ($order['store_credit_used'] ?? 0) > 0 && (int) ($order['store_credit_refunded'] ?? 0) === 0) {
                    Wallet::credit(
                        (int) $order['buyer_id'],
                        (float) $order['store_credit_used'],
                        'credit_order_refund',
                        'order',
                        (int) $order['id'],
                        'Store credit refund for ' . $status . ' order #' . (int) $order['id'],
                        null,
                        'order:' . (int) $order['id'] . ':buyer-refund'
                    );

                    $refund = $this->db->prepare('UPDATE orders SET store_credit_refunded = 1 WHERE id = :id');
                    $refund->execute(['id' => (int) $order['id']]);
                }
            }

            if ($status === 'completed') {
                foreach ($this->itemsForOrder((int) $order['id']) as $item) {
                    $listing = $this->lockListing((int) $item['listing_id']);

                    if ($listing !== null && (int) $listing['quantity'] === 0) {
                        $this->setListingAvailability((int) $listing['id'], 0, 'sold');
                    }
                }

                if ((float) ($order['store_credit_used'] ?? 0) > 0 && (int) ($order['store_credit_settled'] ?? 0) === 0) {
                    Wallet::credit(
                        (int) $order['seller_id'],
                        (float) $order['store_credit_used'],
                        'credit_order_settlement',
                        'order',
                        (int) $order['id'],
                        'Store credit settlement for completed order #' . (int) $order['id'],
                        null,
                        'order:' . (int) $order['id'] . ':seller-settlement'
                    );

                    $settlement = $this->db->prepare('UPDATE orders SET store_credit_settled = 1 WHERE id = :id');
                    $settlement->execute(['id' => (int) $order['id']]);
                }
            }

            if ($status === 'completed' && $order['status'] !== 'buyer_confirmed') {
                throw new RuntimeException('Settlement requires buyer confirmation first.');
            }

            $timestamps = match ($status) {
                'shipped' => ', shipped_at = NOW()',
                'delivered' => ', delivered_at = NOW(), settlement_available_at = DATE_ADD(NOW(), INTERVAL 72 HOUR)',
                'buyer_confirmed' => ', settlement_available_at = NOW()',
                'completed' => ', completed_at = NOW(), settled_at = NOW()',
                'cancelled', 'expired' => ', cancelled_at = NOW()',
                default => '',
            };
            $stateColumns = match ($status) {
                'payment_verified' => ", payment_status = 'verified', fulfillment_status = 'awaiting_fulfillment'",
                'preparing' => ", fulfillment_status = 'preparing'",
                'shipped' => ", fulfillment_status = 'shipped'",
                'ready_for_meetup' => ", fulfillment_status = 'ready_for_meetup'",
                'delivered', 'buyer_confirmed' => ", fulfillment_status = 'delivered'",
                'completed' => ", fulfillment_status = 'completed'",
                'cancelled', 'expired' => ", fulfillment_status = 'cancelled'" . ((int) ($order['store_credit_refunded'] ?? 0) === 1 || (float) ($order['store_credit_used'] ?? 0) > 0 ? ", payment_status = 'refunded'" : ''),
                default => '',
            };
            $statement = $this->db->prepare('UPDATE orders SET status = :status' . $timestamps . $stateColumns . ' WHERE id = :id');
            $statement->execute([
                'id' => (int) $order['id'],
                'status' => $status,
            ]);

            $this->addHistory((int) $order['id'], $order['status'], $status, $changedBy, $note);

            $this->db->commit();
            $title = 'Order #' . (int) $order['id'] . ': ' . ucwords(str_replace('_', ' ', $status));
            $body = $note !== '' ? $note : 'The order status changed to ' . ucwords(str_replace('_', ' ', $status)) . '.';
            if ($changedBy === null) {
                $this->notifySafely((int) $order['buyer_id'], 'order_status', $title, $body, '/orders/show?id=' . (int) $order['id']);
                $this->notifySafely((int) $order['seller_id'], 'order_status', $title, $body, '/orders/show?id=' . (int) $order['id']);
            } else {
                $recipient = $changedBy === (int) $order['buyer_id'] ? (int) $order['seller_id'] : (int) $order['buyer_id'];
                $this->notifySafely($recipient, 'order_status', $title, $body, '/orders/show?id=' . (int) $order['id']);
            }
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function submitPayment(array $order, string $method, string $reference, int $buyerId): void
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                "UPDATE orders SET external_payment_method = :method, payment_reference = :reference,
                 status = 'payment_submitted', payment_status = 'submitted'
                 WHERE id = :id AND buyer_id = :buyer_id AND status = 'pending_payment'"
            );
            $statement->execute(['method' => $method, 'reference' => $reference, 'id' => (int) $order['id'], 'buyer_id' => $buyerId]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('Payment can no longer be submitted for this order.');
            }
            $this->addHistory((int) $order['id'], 'pending_payment', 'payment_submitted', $buyerId, 'Buyer submitted payment details.');
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function submitPaymentWithProof(array $order, string $method, string $reference, int $buyerId, int $proofId): void
    {
        $this->db->beginTransaction();
        try {
            $proof = $this->db->prepare("SELECT id FROM payment_proofs WHERE id = :id AND order_id = :order_id AND uploaded_by = :buyer_id AND status = 'pending' FOR UPDATE");
            $proof->execute(['id' => $proofId, 'order_id' => (int) $order['id'], 'buyer_id' => $buyerId]);
            if (!$proof->fetchColumn()) {
                throw new RuntimeException('The payment proof is unavailable.');
            }
            $statement = $this->db->prepare(
                "UPDATE orders SET external_payment_method = :method, payment_reference = :reference,
                 status = 'payment_submitted', payment_status = 'submitted', fulfillment_status = 'awaiting_payment'
                 WHERE id = :id AND buyer_id = :buyer_id AND status = 'pending_payment'"
            );
            $statement->execute(['method' => $method, 'reference' => $reference, 'id' => (int) $order['id'], 'buyer_id' => $buyerId]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('Payment can no longer be submitted for this order.');
            }
            $this->addHistory((int) $order['id'], 'pending_payment', 'payment_submitted', $buyerId, 'Buyer submitted payment proof #' . $proofId . '.');
            $this->db->commit();
            $this->notifySafely((int) $order['seller_id'], 'payment_submitted', 'Payment proof for order #' . (int) $order['id'], 'The buyer submitted payment proof for review.', '/orders/show?id=' . (int) $order['id']);
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function reviewPaymentProof(int $orderId, int $proofId, string $decision, string $notes, int $sellerId): void
    {
        $this->db->beginTransaction();
        try {
            $order = $this->lockOrder($orderId);
            $proofStatement = $this->db->prepare("SELECT * FROM payment_proofs WHERE id = :id AND order_id = :order_id FOR UPDATE");
            $proofStatement->execute(['id' => $proofId, 'order_id' => $orderId]);
            $proof = $proofStatement->fetch();
            if ($order === null || (int) $order['seller_id'] !== $sellerId || $order['status'] !== 'payment_submitted'
                || !$proof || $proof['status'] !== 'pending') {
                throw new RuntimeException('This payment proof can no longer be reviewed.');
            }

            $approved = $decision === 'approve';
            $proofUpdate = $this->db->prepare(
                'UPDATE payment_proofs SET status = :status, review_notes = :notes, reviewed_by = :reviewed_by, reviewed_at = NOW() WHERE id = :id'
            );
            $proofUpdate->execute(['status' => $approved ? 'approved' : 'rejected', 'notes' => $notes ?: null, 'reviewed_by' => $sellerId, 'id' => $proofId]);

            if ($approved) {
                $orderUpdate = $this->db->prepare("UPDATE orders SET status = 'payment_verified', payment_status = 'verified', fulfillment_status = 'awaiting_fulfillment' WHERE id = :id");
                $orderUpdate->execute(['id' => $orderId]);
                $this->addHistory($orderId, 'payment_submitted', 'payment_verified', $sellerId, 'Seller approved payment proof #' . $proofId . '.');
            } else {
                $orderUpdate = $this->db->prepare("UPDATE orders SET status = 'pending_payment', payment_status = 'rejected', fulfillment_status = 'awaiting_payment', payment_deadline = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = :id");
                $orderUpdate->execute(['id' => $orderId]);
                $this->addHistory($orderId, 'payment_submitted', 'pending_payment', $sellerId, 'Payment proof #' . $proofId . ' rejected: ' . $notes);
            }
            $this->db->commit();
            $this->notifySafely((int) $order['buyer_id'], $approved ? 'payment_approved' : 'payment_rejected', ($approved ? 'Payment approved' : 'Payment proof rejected') . ' for order #' . $orderId, $approved ? 'The seller verified your payment proof.' : 'The seller rejected your payment proof: ' . $notes, '/orders/show?id=' . $orderId);
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function addTracking(array $order, string $carrier, string $reference, int $sellerId): void
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                "UPDATE orders SET tracking_carrier = :carrier, tracking_reference = :reference,
                 status = 'shipped', fulfillment_status = 'shipped', shipped_at = NOW()
                 WHERE id = :id AND seller_id = :seller_id AND status = 'preparing' AND logistics_method = 'lbc'"
            );
            $statement->execute(['carrier' => $carrier, 'reference' => $reference, 'id' => (int) $order['id'], 'seller_id' => $sellerId]);
            if ($statement->rowCount() !== 1) {
                throw new RuntimeException('Tracking cannot be added at this stage.');
            }
            $this->addHistory((int) $order['id'], 'preparing', 'shipped', $sellerId, $carrier . ' tracking: ' . $reference);
            $this->db->commit();
            $this->notifySafely((int) $order['buyer_id'], 'fulfillment_shipped', 'Order #' . (int) $order['id'] . ' shipped', $carrier . ' tracking: ' . $reference, '/orders/show?id=' . (int) $order['id']);
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function expireOverdue(): int
    {
        $statement = $this->db->query(
            "SELECT id FROM orders WHERE status = 'pending_payment' AND payment_deadline IS NOT NULL AND payment_deadline < NOW()"
        );
        $count = 0;
        foreach ($statement->fetchAll() as $row) {
            $order = $this->find((int) $row['id']);
            if ($order !== null && $order['status'] === 'pending_payment') {
                $this->updateStatus($order, 'expired', null, 'Payment deadline expired automatically.');
                $count++;
            }
        }
        return $count;
    }

    public function settleEligibleOrders(): int
    {
        $statement = $this->db->query(
            "SELECT id FROM orders WHERE status = 'delivered' AND settlement_available_at IS NOT NULL
             AND settlement_available_at <= NOW()"
        );
        $count = 0;
        foreach ($statement->fetchAll() as $row) {
            $order = $this->find((int) $row['id']);
            if ($order === null || $order['status'] !== 'delivered') {
                continue;
            }
            $this->updateStatus($order, 'buyer_confirmed', null, 'Buyer confirmation window elapsed; confirmed automatically.');
            $confirmed = $this->find((int) $row['id']);
            if ($confirmed !== null && $confirmed['status'] === 'buyer_confirmed') {
                $this->updateStatus($confirmed, 'completed', null, 'Order settled automatically after the delivery window.');
                $count++;
            }
        }
        return $count;
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    public function logisticsFee(string $logisticsMethod): float
    {
        return $logisticsMethod === 'lbc' ? self::LBC_FEE_PHP : 0.00;
    }

    private function lockedCartItems(int $buyerId): array
    {
        $sql = 'SELECT cart_items.*, cart_items.quantity AS quantity,
                       listings.id AS listing_id, listings.user_id AS seller_id, listings.card_id,
                       listings.quantity AS available_quantity, listings.price_php,
                       listings.status AS listing_status,
                       sellers.account_status AS seller_account_status,
                       sellers.email_verified_at AS seller_email_verified_at
                FROM cart_items
                INNER JOIN listings ON listings.id = cart_items.listing_id
                INNER JOIN users sellers ON sellers.id = listings.user_id
                WHERE cart_items.buyer_id = :buyer_id
                ORDER BY cart_items.id ASC
                FOR UPDATE';

        $statement = $this->db->prepare($sql);
        $statement->execute(['buyer_id' => $buyerId]);

        return $statement->fetchAll();
    }

    private function assertCartCanCheckout(array $cartItems): void
    {
        if ($cartItems === []) {
            throw new RuntimeException('Your cart is empty.');
        }

        $sellerId = (int) $cartItems[0]['seller_id'];

        foreach ($cartItems as $item) {
            if ((int) $item['seller_id'] !== $sellerId) {
                throw new RuntimeException('Checkout supports one seller at a time. Remove items from other sellers first.');
            }

            if ($item['listing_status'] !== 'active') {
                throw new RuntimeException('One of the listings in your cart is no longer active.');
            }

            if (($item['seller_account_status'] ?? '') !== 'active' || empty($item['seller_email_verified_at'])) {
                throw new RuntimeException('One of the sellers is no longer eligible to trade.');
            }

            if ((int) $item['quantity'] < 1 || (int) $item['quantity'] > (int) $item['available_quantity']) {
                throw new RuntimeException('One of the cart quantities is no longer available.');
            }
        }
    }

    private function createOrderItem(int $orderId, array $item): void
    {
        $lineTotal = (float) $item['price_php'] * (int) $item['quantity'];
        $statement = $this->db->prepare('INSERT INTO order_items
                (order_id, listing_id, card_id, quantity, unit_price_php, line_total_php)
            VALUES
                (:order_id, :listing_id, :card_id, :quantity, :unit_price_php, :line_total_php)');

        $statement->execute([
            'order_id' => $orderId,
            'listing_id' => (int) $item['listing_id'],
            'card_id' => (int) $item['card_id'],
            'quantity' => (int) $item['quantity'],
            'unit_price_php' => number_format((float) $item['price_php'], 2, '.', ''),
            'line_total_php' => number_format($lineTotal, 2, '.', ''),
        ]);
    }

    private function lockListing(int $listingId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM listings WHERE id = :id FOR UPDATE');
        $statement->execute(['id' => $listingId]);
        $listing = $statement->fetch();

        return $listing ?: null;
    }

    private function lockOrder(int $orderId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM orders WHERE id = :id FOR UPDATE');
        $statement->execute(['id' => $orderId]);
        return $statement->fetch() ?: null;
    }

    private function restoreListingQuantity(int $listingId, int $quantity): void
    {
        $listing = $this->lockListing($listingId);

        if ($listing === null || $listing['status'] === 'cancelled') {
            return;
        }

        $restoredQuantity = (int) $listing['quantity'] + $quantity;
        $status = $listing['status'] === 'sold' ? 'sold' : 'active';
        $this->setListingAvailability($listingId, $restoredQuantity, $status);
    }

    private function setListingAvailability(int $listingId, int $quantity, string $status): void
    {
        $statement = $this->db->prepare('UPDATE listings SET quantity = :quantity, status = :status WHERE id = :id');
        $statement->execute([
            'id' => $listingId,
            'quantity' => $quantity,
            'status' => $status,
        ]);
    }

    private function clearCart(int $buyerId): void
    {
        $statement = $this->db->prepare('DELETE FROM cart_items WHERE buyer_id = :buyer_id');
        $statement->execute(['buyer_id' => $buyerId]);
    }

    private function subtotal(array $cartItems): float
    {
        $subtotal = 0.00;

        foreach ($cartItems as $item) {
            $subtotal += (float) $item['price_php'] * (int) $item['quantity'];
        }

        return $subtotal;
    }

    private function totalQuantity(array $cartItems): int
    {
        $quantity = 0;

        foreach ($cartItems as $item) {
            $quantity += (int) $item['quantity'];
        }

        return $quantity;
    }

    private function walletPaymentMethod(float $storeCreditUsed, float $cashAmountDue): string
    {
        if ($storeCreditUsed <= 0) {
            return 'cash_gcach_bank';
        }

        return $cashAmountDue <= 0 ? 'store_credit' : 'mixed';
    }

    private function addHistory(int $orderId, ?string $fromStatus, string $toStatus, ?int $changedBy, string $note): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO order_status_history (order_id, from_status, to_status, changed_by, note)
             VALUES (:order_id, :from_status, :to_status, :changed_by, :note)'
        );
        $statement->execute([
            'order_id' => $orderId,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $changedBy,
            'note' => $note === '' ? null : mb_substr($note, 0, 500),
        ]);
    }

    private function notifySafely(int $userId, string $type, string $title, string $body, string $actionPath): void
    {
        try {
            NotificationService::send($userId, $type, $title, $body, $actionPath);
        } catch (Throwable $exception) {
            error_log('MTGHub notification creation failed: ' . $exception->getMessage());
        }
    }

    private function baseSelect(): string
    {
        return "SELECT orders.*,
                       buyers.username AS buyer_username, sellers.username AS seller_username,
                       COALESCE(order_totals.item_count, 0) AS item_count,
                       COALESCE(order_totals.subtotal_php, orders.unit_price_php) AS subtotal_php,
                       order_totals.cards_summary
                FROM orders
                INNER JOIN users buyers ON buyers.id = orders.buyer_id
                INNER JOIN users sellers ON sellers.id = orders.seller_id
                LEFT JOIN (
                    SELECT order_items.order_id,
                           COUNT(order_items.id) AS item_count,
                           SUM(order_items.line_total_php) AS subtotal_php,
                           GROUP_CONCAT(CONCAT(cards.card_name, ' x', order_items.quantity) ORDER BY order_items.id SEPARATOR ', ') AS cards_summary
                    FROM order_items
                    INNER JOIN cards ON cards.id = order_items.card_id
                    GROUP BY order_items.order_id
                ) order_totals ON order_totals.order_id = orders.id";
    }
}
