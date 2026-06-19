<?php

class Notification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO notifications (user_id, type, title, body, action_url) VALUES (:user_id, :type, :title, :body, :action_url)'
        );
        $statement->execute($data);
        return (int) $this->db->lastInsertId();
    }

    public function forUser(int $userId, int $limit = 100): array
    {
        $statement = $this->db->prepare('SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC, id DESC LIMIT :limit');
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', max(1, min($limit, 200)), PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }

    public function unreadCount(int $userId): int
    {
        $statement = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL');
        $statement->execute(['user_id' => $userId]);
        return (int) $statement->fetchColumn();
    }

    public function markRead(int $id, int $userId): void
    {
        $statement = $this->db->prepare('UPDATE notifications SET read_at = COALESCE(read_at, NOW()) WHERE id = :id AND user_id = :user_id');
        $statement->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function markAllRead(int $userId): void
    {
        $statement = $this->db->prepare('UPDATE notifications SET read_at = COALESCE(read_at, NOW()) WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
    }

    public function markEmailed(int $id): void
    {
        $statement = $this->db->prepare('UPDATE notifications SET emailed_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function preferences(int $userId): array
    {
        $statement = $this->db->prepare('SELECT * FROM notification_preferences WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetch() ?: ['user_id' => $userId, 'email_order_updates' => 1, 'email_messages' => 0, 'email_offers' => 1];
    }

    public function savePreferences(int $userId, array $preferences): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO notification_preferences (user_id, email_order_updates, email_messages, email_offers)
             VALUES (:user_id, :email_order_updates, :email_messages, :email_offers)
             ON DUPLICATE KEY UPDATE email_order_updates = VALUES(email_order_updates), email_messages = VALUES(email_messages), email_offers = VALUES(email_offers)'
        );
        $statement->execute(['user_id' => $userId] + $preferences);
    }
}
