<?php

require_once APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Wallet.php';

class MtgHubBuylistOrder
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function createOrder($userId, $buylistEntryId, $quantity, $declaredCondition, $payoutMethod, $remarks): int
    {
        $userId = (int) $userId;
        $buylistEntryId = (int) $buylistEntryId;
        $quantity = (int) $quantity;

        $this->db->beginTransaction();

        try {
            $entry = $this->findEntryForUpdate($buylistEntryId);
            if ($entry === null || (int) $entry['is_active'] !== 1) {
                throw new RuntimeException('That MTGHub buylist entry is not available.');
            }

            $remaining = max(0, (int) $entry['target_quantity'] - (int) $entry['received_quantity']);
            if ($quantity < 1 || $quantity > $remaining) {
                throw new RuntimeException('Submitted quantity must fit the remaining target quantity.');
            }

            $unitOffer = $payoutMethod === 'store_credit' ? (float) $entry['credit_offer'] : (float) $entry['cash_offer'];
            $estimatedTotal = $unitOffer * $quantity;

            $orderInsert = $this->db->prepare(
                'INSERT INTO mtghub_buylist_orders
                    (user_id, status, payout_method, estimated_total, user_remarks)
                 VALUES
                    (:user_id, :status, :payout_method, :estimated_total, :user_remarks)'
            );
            $orderInsert->execute([
                'user_id' => $userId,
                'status' => 'pending_receipt',
                'payout_method' => $payoutMethod,
                'estimated_total' => number_format($estimatedTotal, 2, '.', ''),
                'user_remarks' => $remarks !== '' ? $remarks : null,
            ]);
            $orderId = (int) $this->db->lastInsertId();

            $itemInsert = $this->db->prepare(
                'INSERT INTO mtghub_buylist_order_items
                    (order_id, buylist_entry_id, card_id, declared_condition, quantity_submitted,
                     cash_offer_snapshot, credit_offer_snapshot, estimated_subtotal, status)
                 VALUES
                    (:order_id, :buylist_entry_id, :card_id, :declared_condition, :quantity_submitted,
                     :cash_offer_snapshot, :credit_offer_snapshot, :estimated_subtotal, :status)'
            );
            $itemInsert->execute([
                'order_id' => $orderId,
                'buylist_entry_id' => $buylistEntryId,
                'card_id' => (int) $entry['card_id'],
                'declared_condition' => $declaredCondition !== '' ? $declaredCondition : null,
                'quantity_submitted' => $quantity,
                'cash_offer_snapshot' => number_format((float) $entry['cash_offer'], 2, '.', ''),
                'credit_offer_snapshot' => number_format((float) $entry['credit_offer'], 2, '.', ''),
                'estimated_subtotal' => number_format($estimatedTotal, 2, '.', ''),
                'status' => 'pending',
            ]);

            $this->db->commit();
            return $orderId;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function findForUser($orderId, $userId): ?array
    {
        $order = $this->findOrder((int) $orderId, 'AND mtghub_buylist_orders.user_id = :user_id', ['user_id' => (int) $userId]);
        return $order ? $this->withItems($order) : null;
    }

    public function findForAdmin($orderId): ?array
    {
        $order = $this->findOrder((int) $orderId);
        return $order ? $this->withItems($order) : null;
    }

    public function listForUser($userId): array
    {
        $statement = $this->db->prepare(
            'SELECT mtghub_buylist_orders.*, COUNT(mtghub_buylist_order_items.id) AS item_count
             FROM mtghub_buylist_orders
             LEFT JOIN mtghub_buylist_order_items ON mtghub_buylist_order_items.order_id = mtghub_buylist_orders.id
             WHERE mtghub_buylist_orders.user_id = :user_id
             GROUP BY mtghub_buylist_orders.id
             ORDER BY mtghub_buylist_orders.created_at DESC, mtghub_buylist_orders.id DESC'
        );
        $statement->execute(['user_id' => (int) $userId]);
        return $statement->fetchAll();
    }

    public function listForAdmin(): array
    {
        $sql = 'SELECT mtghub_buylist_orders.*, users.username, users.email, COUNT(mtghub_buylist_order_items.id) AS item_count
                FROM mtghub_buylist_orders
                INNER JOIN users ON users.id = mtghub_buylist_orders.user_id
                LEFT JOIN mtghub_buylist_order_items ON mtghub_buylist_order_items.order_id = mtghub_buylist_orders.id
                GROUP BY mtghub_buylist_orders.id
                ORDER BY mtghub_buylist_orders.created_at DESC, mtghub_buylist_orders.id DESC';
        return $this->db->query($sql)->fetchAll();
    }

    public function markReceived($orderId): bool
    {
        $statement = $this->db->prepare(
            "UPDATE mtghub_buylist_orders
             SET status = 'received', received_at = COALESCE(received_at, CURRENT_TIMESTAMP)
             WHERE id = :id AND status IN ('pending_receipt')"
        );
        return $statement->execute(['id' => (int) $orderId]);
    }

    public function markUnderInspection($orderId): bool
    {
        $statement = $this->db->prepare(
            "UPDATE mtghub_buylist_orders
             SET status = 'under_inspection', inspected_at = COALESCE(inspected_at, CURRENT_TIMESTAMP)
             WHERE id = :id AND status IN ('received', 'pending_receipt')"
        );
        return $statement->execute(['id' => (int) $orderId]);
    }

    public function inspectAndApprove($orderId, $items, $adminRemarks): bool
    {
        $this->db->beginTransaction();

        try {
            $order = $this->findOrderForUpdate((int) $orderId);
            if ($order === null || !in_array($order['status'], ['received', 'under_inspection'], true)) {
                throw new RuntimeException('Only received or under inspection orders can be approved.');
            }

            $orderItems = $this->itemsForOrder((int) $orderId, true);
            $approvedTotal = 0.0;
            $submittedTotal = 0;
            $acceptedTotal = 0;

            foreach ($orderItems as $item) {
                $itemId = (int) $item['id'];
                $quantityAccepted = max(0, (int) ($items[$itemId]['quantity_accepted'] ?? 0));
                $quantityAccepted = min($quantityAccepted, (int) $item['quantity_submitted']);
                $approvedCondition = trim((string) ($items[$itemId]['approved_condition'] ?? $item['declared_condition'] ?? ''));
                $itemRemarks = trim((string) ($items[$itemId]['admin_remarks'] ?? ''));
                $unitOffer = $order['payout_method'] === 'store_credit'
                    ? (float) $item['credit_offer_snapshot']
                    : (float) $item['cash_offer_snapshot'];
                $approvedSubtotal = $unitOffer * $quantityAccepted;
                $itemStatus = $quantityAccepted < 1 ? 'rejected' : ($quantityAccepted < (int) $item['quantity_submitted'] ? 'partially_accepted' : 'accepted');

                $update = $this->db->prepare(
                    'UPDATE mtghub_buylist_order_items SET
                        approved_condition = :approved_condition,
                        quantity_accepted = :quantity_accepted,
                        approved_subtotal = :approved_subtotal,
                        status = :status,
                        admin_remarks = :admin_remarks
                     WHERE id = :id'
                );
                $update->execute([
                    'id' => $itemId,
                    'approved_condition' => $approvedCondition !== '' ? $approvedCondition : null,
                    'quantity_accepted' => $quantityAccepted,
                    'approved_subtotal' => number_format($approvedSubtotal, 2, '.', ''),
                    'status' => $itemStatus,
                    'admin_remarks' => $itemRemarks !== '' ? $itemRemarks : null,
                ]);

                if ($quantityAccepted > 0) {
                    $entryUpdate = $this->db->prepare(
                        'UPDATE mtghub_buylist_entries
                         SET received_quantity = received_quantity + :quantity
                         WHERE id = :id'
                    );
                    $entryUpdate->execute([
                        'id' => (int) $item['buylist_entry_id'],
                        'quantity' => $quantityAccepted,
                    ]);
                }

                $submittedTotal += (int) $item['quantity_submitted'];
                $acceptedTotal += $quantityAccepted;
                $approvedTotal += $approvedSubtotal;
            }

            $status = $acceptedTotal < 1 ? 'rejected' : ($acceptedTotal < $submittedTotal ? 'partially_accepted' : 'accepted');
            $orderUpdate = $this->db->prepare(
                'UPDATE mtghub_buylist_orders SET
                    status = :status,
                    approved_total = :approved_total,
                    admin_remarks = :admin_remarks,
                    inspected_at = COALESCE(inspected_at, CURRENT_TIMESTAMP)
                 WHERE id = :id'
            );
            $orderUpdate->execute([
                'id' => (int) $orderId,
                'status' => $status,
                'approved_total' => number_format($approvedTotal, 2, '.', ''),
                'admin_remarks' => $adminRemarks !== '' ? $adminRemarks : null,
            ]);

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function reject($orderId, $adminRemarks): bool
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                "UPDATE mtghub_buylist_orders
                 SET status = 'rejected', approved_total = 0.00, admin_remarks = :admin_remarks,
                     inspected_at = COALESCE(inspected_at, CURRENT_TIMESTAMP)
                 WHERE id = :id AND status IN ('pending_receipt', 'received', 'under_inspection')"
            );
            $statement->execute([
                'id' => (int) $orderId,
                'admin_remarks' => $adminRemarks !== '' ? $adminRemarks : null,
            ]);

            $items = $this->db->prepare(
                "UPDATE mtghub_buylist_order_items
                 SET status = 'rejected', quantity_accepted = 0, approved_subtotal = 0.00
                 WHERE order_id = :order_id"
            );
            $items->execute(['order_id' => (int) $orderId]);

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function completeCashPayout($orderId, $adminRemarks): bool
    {
        $this->db->beginTransaction();
        try {
            $order = $this->findOrderForUpdate((int) $orderId);
            if ($order === null || $order['payout_method'] !== 'cash' || !in_array($order['status'], ['accepted', 'partially_accepted'], true)) {
                throw new RuntimeException('Only accepted cash payout orders can be completed.');
            }

            $statement = $this->db->prepare(
                "UPDATE mtghub_buylist_orders
                 SET status = 'completed', cash_payout_completed = 1,
                     admin_remarks = :admin_remarks, completed_at = CURRENT_TIMESTAMP
                 WHERE id = :id"
            );
            $statement->execute([
                'id' => (int) $orderId,
                'admin_remarks' => $adminRemarks !== '' ? $adminRemarks : $order['admin_remarks'],
            ]);

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function creditStoreCreditIfApproved($orderId): bool
    {
        $this->db->beginTransaction();
        try {
            $order = $this->findOrderForUpdate((int) $orderId);
            if ($order === null || $order['payout_method'] !== 'store_credit') {
                throw new RuntimeException('This order is not a store credit payout.');
            }

            if (!in_array($order['status'], ['accepted', 'partially_accepted'], true)) {
                throw new RuntimeException('Only accepted store credit orders can be credited.');
            }

            if ((int) $order['store_credit_credited'] === 1) {
                $this->db->commit();
                return false;
            }

            $amount = (float) $order['approved_total'];
            if ($amount <= 0) {
                throw new RuntimeException('Approved store credit amount must be greater than zero.');
            }

            Wallet::credit(
                (int) $order['user_id'],
                $amount,
                'credit_buylist_settlement',
                'mtghub_buylist_order',
                (int) $order['id'],
                'MTGHub Buylist settlement for Order #' . (int) $order['id'],
                is_admin() ? (int) current_user()['id'] : null
            );

            $statement = $this->db->prepare(
                "UPDATE mtghub_buylist_orders
                 SET status = 'completed', store_credit_credited = 1, completed_at = CURRENT_TIMESTAMP
                 WHERE id = :id"
            );
            $statement->execute(['id' => (int) $orderId]);

            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    private function findEntryForUpdate(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM mtghub_buylist_entries WHERE id = :id LIMIT 1 FOR UPDATE');
        $statement->execute(['id' => $id]);
        $entry = $statement->fetch();

        return $entry ?: null;
    }

    private function findOrderForUpdate(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM mtghub_buylist_orders WHERE id = :id LIMIT 1 FOR UPDATE');
        $statement->execute(['id' => $id]);
        $order = $statement->fetch();

        return $order ?: null;
    }

    private function findOrder(int $id, string $extraWhere = '', array $params = []): ?array
    {
        $statement = $this->db->prepare(
            'SELECT mtghub_buylist_orders.*, users.username, users.email
             FROM mtghub_buylist_orders
             INNER JOIN users ON users.id = mtghub_buylist_orders.user_id
             WHERE mtghub_buylist_orders.id = :id ' . $extraWhere . '
             LIMIT 1'
        );
        $statement->execute(array_merge(['id' => $id], $params));
        $order = $statement->fetch();

        return $order ?: null;
    }

    private function withItems(array $order): array
    {
        $order['items'] = $this->itemsForOrder((int) $order['id'], false);
        return $order;
    }

    private function itemsForOrder(int $orderId, bool $forUpdate): array
    {
        $sql = 'SELECT mtghub_buylist_order_items.*, cards.card_name, cards.set_name, cards.collector_number
                FROM mtghub_buylist_order_items
                INNER JOIN cards ON cards.id = mtghub_buylist_order_items.card_id
                WHERE mtghub_buylist_order_items.order_id = :order_id
                ORDER BY mtghub_buylist_order_items.id ASC' . ($forUpdate ? ' FOR UPDATE' : '');
        $statement = $this->db->prepare($sql);
        $statement->execute(['order_id' => $orderId]);

        return $statement->fetchAll();
    }
}
