<?php

class OrderMessage
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(int $orderId, int $senderId, string $body, bool $senderIsBuyer): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO order_messages (order_id, sender_id, body, buyer_read_at, seller_read_at)
             VALUES (:order_id, :sender_id, :body, ' . ($senderIsBuyer ? 'NOW(), NULL' : 'NULL, NOW()') . ')'
        );
        $statement->execute(['order_id' => $orderId, 'sender_id' => $senderId, 'body' => $body]);
        return (int) $this->db->lastInsertId();
    }

    public function forOrder(int $orderId): array
    {
        $statement = $this->db->prepare(
            'SELECT order_messages.*, users.username AS sender_username
             FROM order_messages INNER JOIN users ON users.id = order_messages.sender_id
             WHERE order_messages.order_id = :order_id ORDER BY order_messages.created_at ASC, order_messages.id ASC'
        );
        $statement->execute(['order_id' => $orderId]);
        return $statement->fetchAll();
    }

    public function markRead(int $orderId, bool $viewerIsBuyer): void
    {
        $column = $viewerIsBuyer ? 'buyer_read_at' : 'seller_read_at';
        $statement = $this->db->prepare('UPDATE order_messages SET ' . $column . ' = COALESCE(' . $column . ', NOW()) WHERE order_id = :order_id');
        $statement->execute(['order_id' => $orderId]);
    }
}
