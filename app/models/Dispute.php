<?php

require_once APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Wallet.php';
require_once APP_PATH . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'NotificationService.php';

class Dispute
{
    private PDO $db;

    public function __construct() { $this->db = Database::connection(); }

    public function activeForOrder(int $orderId): ?array
    {
        $statement = $this->db->prepare("SELECT * FROM order_disputes WHERE order_id = :order_id AND status IN ('open','reviewing') ORDER BY id DESC LIMIT 1");
        $statement->execute(['order_id' => $orderId]);
        return $statement->fetch() ?: null;
    }

    public function forOrder(int $orderId): array
    {
        $statement = $this->db->prepare('SELECT order_disputes.*, opener.username AS opener_username, resolver.username AS resolver_username FROM order_disputes INNER JOIN users opener ON opener.id = order_disputes.opened_by LEFT JOIN users resolver ON resolver.id = order_disputes.resolved_by WHERE order_id = :order_id ORDER BY id DESC');
        $statement->execute(['order_id' => $orderId]);
        return $statement->fetchAll();
    }

    public function allForAdmin(): array
    {
        return $this->db->query("SELECT order_disputes.*, orders.buyer_id, orders.seller_id, orders.total_price_php, orders.store_credit_used, buyer.username AS buyer_username, seller.username AS seller_username, opener.username AS opener_username FROM order_disputes INNER JOIN orders ON orders.id = order_disputes.order_id INNER JOIN users buyer ON buyer.id = orders.buyer_id INNER JOIN users seller ON seller.id = orders.seller_id INNER JOIN users opener ON opener.id = order_disputes.opened_by ORDER BY FIELD(order_disputes.status,'open','reviewing','resolved'), order_disputes.created_at DESC")->fetchAll();
    }

    public function open(array $order, int $userId, string $reason, string $details, string $evidence): int
    {
        $eligible = ['payment_verified','preparing','shipped','ready_for_meetup','delivered','buyer_confirmed','completed'];
        if (!in_array($order['status'], $eligible, true)) throw new RuntimeException('This order is not eligible for a dispute.');
        if ($order['status'] === 'completed' && !empty($order['completed_at']) && strtotime($order['completed_at']) < time() - 604800) throw new RuntimeException('Completed orders can be disputed for up to seven days.');
        if ((int) $order['buyer_id'] !== $userId && (int) $order['seller_id'] !== $userId) throw new RuntimeException('You are not a participant in this order.');

        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT * FROM orders WHERE id = :id FOR UPDATE'); $lock->execute(['id' => (int) $order['id']]); $current = $lock->fetch();
            if (!$current || $current['status'] !== $order['status'] || $this->activeForOrder((int) $order['id'])) throw new RuntimeException('This order already changed or has an active dispute.');
            $insert = $this->db->prepare('INSERT INTO order_disputes (order_id,opened_by,reason,details,evidence_notes,order_status_before) VALUES (:order_id,:opened_by,:reason,:details,:evidence,:before_status)');
            $insert->execute(['order_id' => (int) $order['id'], 'opened_by' => $userId, 'reason' => $reason, 'details' => $details, 'evidence' => $evidence ?: null, 'before_status' => $order['status']]);
            $id = (int) $this->db->lastInsertId();
            $this->db->prepare("UPDATE orders SET status='disputed', settlement_available_at=NULL WHERE id=:id")->execute(['id' => (int) $order['id']]);
            $this->db->prepare("INSERT INTO order_status_history (order_id,from_status,to_status,changed_by,note) VALUES (:order_id,:from_status,'disputed',:changed_by,:note)")->execute(['order_id' => (int) $order['id'], 'from_status' => $order['status'], 'changed_by' => $userId, 'note' => 'Dispute #' . $id . ' opened: ' . $reason]);
            $this->db->commit();
            $other = $userId === (int) $order['buyer_id'] ? (int) $order['seller_id'] : (int) $order['buyer_id'];
            NotificationService::send($other, 'order_dispute', 'Dispute opened for order #' . (int) $order['id'], 'Settlement is frozen while MTGHub reviews the dispute.', '/orders/show?id=' . (int) $order['id']);
            return $id;
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }

    public function resolve(int $disputeId, string $resolution, float $refundTotal, string $notes, int $adminId): void
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare("SELECT order_disputes.*, orders.buyer_id, orders.seller_id, orders.total_price_php, orders.store_credit_used, orders.store_credit_refunded_amount, orders.store_credit_settled FROM order_disputes INNER JOIN orders ON orders.id=order_disputes.order_id WHERE order_disputes.id=:id FOR UPDATE");
            $statement->execute(['id' => $disputeId]); $dispute = $statement->fetch();
            if (!$dispute || !in_array($dispute['status'], ['open','reviewing'], true)) throw new RuntimeException('This dispute can no longer be resolved.');
            $isRefund = in_array($resolution, ['full_refund','partial_refund'], true);
            $orderTotal = (float) $dispute['total_price_php'];
            if ($resolution === 'full_refund') $refundTotal = $orderTotal;
            if ($isRefund && ($refundTotal <= 0 || $refundTotal > $orderTotal || ($resolution === 'partial_refund' && $refundTotal >= $orderTotal))) throw new RuntimeException('Enter a valid partial refund below the order total.');
            if (!$isRefund) $refundTotal = 0;

            $remainingStoreCredit = max(0, (float) $dispute['store_credit_used'] - (float) $dispute['store_credit_refunded_amount']);
            $storeCreditRefund = min($remainingStoreCredit, $refundTotal);
            $externalRefund = max(0, $refundTotal - $storeCreditRefund);
            if ($storeCreditRefund > 0 && (int) $dispute['store_credit_settled'] === 1) {
                Wallet::debit((int) $dispute['seller_id'], $storeCreditRefund, 'debit_order_refund_recovery', 'dispute', $disputeId, 'Settlement reversal for dispute #' . $disputeId, $adminId, 'dispute:' . $disputeId . ':seller-recovery');
            }
            if ($storeCreditRefund > 0) {
                Wallet::credit((int) $dispute['buyer_id'], $storeCreditRefund, 'credit_order_refund', 'dispute', $disputeId, 'Buyer refund for dispute #' . $disputeId, $adminId, 'dispute:' . $disputeId . ':buyer-refund');
            }

            if ($isRefund) {
                $newRefunded = (float) $dispute['store_credit_refunded_amount'] + $storeCreditRefund;
                $updateOrder = $this->db->prepare("UPDATE orders SET status='refunded',payment_status='refunded',fulfillment_status='cancelled',settlement_available_at=NULL,store_credit_refunded_amount=:amount,store_credit_refunded=IF(:amount_full>=store_credit_used,1,0) WHERE id=:id");
                $updateOrder->execute(['amount' => $newRefunded, 'amount_full' => $newRefunded, 'id' => (int) $dispute['order_id']]);
                $restoredStatus = 'refunded';
            } else {
                $restoredStatus = $dispute['order_status_before'];
                $extra = $restoredStatus === 'delivered' ? ', settlement_available_at=DATE_ADD(NOW(),INTERVAL 24 HOUR)' : ($restoredStatus === 'buyer_confirmed' ? ', settlement_available_at=NOW()' : '');
                $this->db->prepare('UPDATE orders SET status=:status' . $extra . ' WHERE id=:id')->execute(['status' => $restoredStatus, 'id' => (int) $dispute['order_id']]);
            }
            $update = $this->db->prepare("UPDATE order_disputes SET status='resolved',resolution=:resolution,refund_total=:refund_total,refund_store_credit=:refund_store_credit,refund_external=:refund_external,resolution_notes=:notes,resolved_by=:admin,resolved_at=NOW() WHERE id=:id");
            $update->execute(['resolution' => $resolution, 'refund_total' => $refundTotal, 'refund_store_credit' => $storeCreditRefund, 'refund_external' => $externalRefund, 'notes' => $notes, 'admin' => $adminId, 'id' => $disputeId]);
            $this->db->prepare("INSERT INTO order_status_history (order_id,from_status,to_status,changed_by,note) VALUES (:order_id,'disputed',:to_status,:admin,:note)")->execute(['order_id' => (int) $dispute['order_id'], 'to_status' => $restoredStatus, 'admin' => $adminId, 'note' => 'Dispute #' . $disputeId . ' resolved: ' . $resolution]);
            $this->db->commit();
            $body = 'Resolution: ' . str_replace('_', ' ', $resolution) . '. ' . $notes;
            NotificationService::send((int) $dispute['buyer_id'], 'order_dispute', 'Dispute resolved for order #' . (int) $dispute['order_id'], $body, '/orders/show?id=' . (int) $dispute['order_id']);
            NotificationService::send((int) $dispute['seller_id'], 'order_dispute', 'Dispute resolved for order #' . (int) $dispute['order_id'], $body, '/orders/show?id=' . (int) $dispute['order_id']);
        } catch (Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
}
