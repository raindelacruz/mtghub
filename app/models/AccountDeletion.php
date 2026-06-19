<?php

class AccountDeletion
{
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }

    public function pendingForUser(int $userId): ?array
    {
        $statement = $this->db->prepare("SELECT * FROM account_deletion_requests WHERE user_id=? AND status='pending' ORDER BY id DESC LIMIT 1");
        $statement->execute([$userId]);
        return $statement->fetch() ?: null;
    }

    public function request(int $userId, string $password, string $ip): void
    {
        $user = (new User())->findById($userId);
        if (!$user || !password_verify($password, $user['password_hash'])) throw new RuntimeException('Current password is incorrect.');
        if ($user['role'] === 'admin') throw new RuntimeException('Transfer or remove administrator access before deleting this account.');
        if ($this->pendingForUser($userId)) throw new RuntimeException('Account deletion is already scheduled.');
        $block = $this->blockReason($userId);
        if ($block !== null) throw new RuntimeException($block);
        $this->db->beginTransaction();
        try {
            $scheduled = date('Y-m-d H:i:s', time() + 30 * 86400);
            $statement = $this->db->prepare('INSERT INTO account_deletion_requests (user_id,scheduled_for,request_ip_hash) VALUES (?,?,?)');
            $statement->execute([$userId, $scheduled, hash('sha256', $ip)]);
            $this->db->prepare('UPDATE users SET deletion_requested_at=NOW(),deletion_scheduled_for=? WHERE id=?')->execute([$scheduled, $userId]);
            $this->db->commit();
        } catch (Throwable $error) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $error; }
    }

    public function cancel(int $userId): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("UPDATE account_deletion_requests SET status='cancelled' WHERE user_id=? AND status='pending'")->execute([$userId]);
            $this->db->prepare('UPDATE users SET deletion_requested_at=NULL,deletion_scheduled_for=NULL WHERE id=?')->execute([$userId]);
            $this->db->commit();
        } catch (Throwable $error) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $error; }
    }

    public function processDue(): array
    {
        $requests = $this->db->query("SELECT * FROM account_deletion_requests WHERE status='pending' AND scheduled_for<=NOW() ORDER BY id")->fetchAll();
        $result = ['completed' => 0, 'blocked' => 0];
        foreach ($requests as $request) {
            $reason = $this->blockReason((int) $request['user_id']);
            if ($reason !== null) {
                $this->db->prepare("UPDATE account_deletion_requests SET status='blocked',blocked_reason=? WHERE id=?")->execute([$reason, $request['id']]);
                $result['blocked']++;
                continue;
            }
            $this->anonymize((int) $request['user_id'], (int) $request['id']);
            $result['completed']++;
        }
        return $result;
    }

    private function blockReason(int $userId): ?string
    {
        $active = $this->db->prepare("SELECT COUNT(*) FROM orders WHERE (buyer_id=? OR seller_id=?) AND status NOT IN ('completed','cancelled','expired','refunded')");
        $active->execute([$userId, $userId]);
        if ((int) $active->fetchColumn() > 0) return 'Resolve all active marketplace orders before requesting deletion.';
        $disputes = $this->db->prepare("SELECT COUNT(*) FROM order_disputes d INNER JOIN orders o ON o.id=d.order_id WHERE (o.buyer_id=? OR o.seller_id=?) AND d.status IN ('open','reviewing')");
        $disputes->execute([$userId, $userId]);
        if ((int) $disputes->fetchColumn() > 0) return 'Resolve all open disputes before requesting deletion.';
        if (abs(Wallet::getBalance($userId)) > 0.001) return 'Your store-credit balance must be zero before requesting deletion.';
        return null;
    }

    private function anonymize(int $userId, int $requestId): void
    {
        $this->db->beginTransaction();
        try {
            $anonymous = 'deleted_' . $userId;
            $this->db->prepare("UPDATE listings SET status='cancelled' WHERE user_id=? AND status IN ('active','reserved')")->execute([$userId]);
            $this->db->prepare('DELETE FROM cart_items WHERE buyer_id=?')->execute([$userId]);
            $this->db->prepare('DELETE FROM wishlist_items WHERE user_id=?')->execute([$userId]);
            $this->db->prepare("UPDATE users SET username=?,email=?,first_name='Deleted',middle_initial='',last_name='User',contact_number='',password_hash=?,address_number='',address_street='',address_barangay='',address_province='',address_city='',address_postal_code='',shipping_number='',shipping_street='',shipping_barangay='',shipping_province='',shipping_city='',shipping_postal_code='',city='Deleted',province='Deleted',seller_bio=NULL,account_status='banned',email_verified_at=NULL,deletion_requested_at=NULL,deletion_scheduled_for=NULL,anonymized_at=NOW() WHERE id=?")->execute([$anonymous, $anonymous . '@deleted.invalid', password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT), $userId]);
            $this->db->prepare("UPDATE account_deletion_requests SET status='completed',completed_at=NOW() WHERE id=?")->execute([$requestId]);
            $this->db->commit();
        } catch (Throwable $error) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $error; }
    }
}
