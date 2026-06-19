<?php

class PaymentProof
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO payment_proofs
             (order_id, uploaded_by, original_name, stored_name, mime_type, file_size, image_width, image_height, sha256)
             VALUES (:order_id, :uploaded_by, :original_name, :stored_name, :mime_type, :file_size, :image_width, :image_height, :sha256)'
        );
        $statement->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function forOrder(int $orderId): array
    {
        $statement = $this->db->prepare(
            'SELECT payment_proofs.*, uploader.username AS uploader_username, reviewer.username AS reviewer_username
             FROM payment_proofs
             INNER JOIN users uploader ON uploader.id = payment_proofs.uploaded_by
             LEFT JOIN users reviewer ON reviewer.id = payment_proofs.reviewed_by
             WHERE payment_proofs.order_id = :order_id ORDER BY payment_proofs.created_at DESC'
        );
        $statement->execute(['order_id' => $orderId]);
        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT payment_proofs.*, orders.buyer_id, orders.seller_id, orders.status AS order_status
             FROM payment_proofs INNER JOIN orders ON orders.id = payment_proofs.order_id
             WHERE payment_proofs.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function hasPending(int $orderId): bool
    {
        $statement = $this->db->prepare("SELECT COUNT(*) FROM payment_proofs WHERE order_id = :order_id AND status = 'pending'");
        $statement->execute(['order_id' => $orderId]);
        return (int) $statement->fetchColumn() > 0;
    }

    public function delete(int $id): void
    {
        $statement = $this->db->prepare('DELETE FROM payment_proofs WHERE id = :id');
        $statement->execute(['id' => $id]);
    }
}
