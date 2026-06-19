<?php

class Listing
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function search(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT listings.*, cards.card_name, cards.set_name, cards.collector_number,
                       cards.rarity, cards.color, cards.type_line, cards.image_url, users.username,
                       users.email_verified_at,
                       (
                           SELECT COUNT(*)
                           FROM wishlist_items
                           INNER JOIN cards AS buylist_cards ON buylist_cards.id = wishlist_items.card_id
                           WHERE LOWER(TRIM(buylist_cards.card_name)) = LOWER(TRIM(cards.card_name))
                             AND wishlist_items.user_id <> listings.user_id
                       ) AS buylist_demand_count
                FROM listings
                INNER JOIN cards ON cards.id = listings.card_id
                INNER JOIN users ON users.id = listings.user_id
                WHERE listings.status = :active_status AND users.account_status = 'active'
                  AND users.email_verified_at IS NOT NULL";
        $params = ['active_status' => 'active'];
        $this->appendMarketplaceFilters($sql, $params, $filters);
        $sql .= ' ORDER BY listings.created_at DESC LIMIT :limit OFFSET :offset';

        $statement = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $statement->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function countSearch(array $filters = []): int
    {
        $sql = "SELECT COUNT(*)
                FROM listings
                INNER JOIN cards ON cards.id = listings.card_id
                INNER JOIN users ON users.id = listings.user_id
                WHERE listings.status = :active_status AND users.account_status = 'active'
                  AND users.email_verified_at IS NOT NULL";
        $params = ['active_status' => 'active'];
        $this->appendMarketplaceFilters($sql, $params, $filters);

        $statement = $this->db->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }

    public function activeForCard(int $cardId): array
    {
        $sql = "SELECT listings.*, users.username, users.email_verified_at
                FROM listings
                INNER JOIN users ON users.id = listings.user_id
                WHERE listings.card_id = :card_id AND listings.status = :status
                  AND users.account_status = 'active' AND users.email_verified_at IS NOT NULL
                ORDER BY listings.price_php ASC, listings.created_at DESC";

        $statement = $this->db->prepare($sql);
        $statement->execute([
            'card_id' => $cardId,
            'status' => 'active',
        ]);

        return $statement->fetchAll();
    }

    public function forUser(int $userId): array
    {
        $sql = 'SELECT listings.*, cards.card_name, cards.set_name, cards.collector_number
                FROM listings
                INNER JOIN cards ON cards.id = listings.card_id
                WHERE listings.user_id = :user_id
                ORDER BY listings.created_at DESC';

        $statement = $this->db->prepare($sql);
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function activeForSeller(int $userId): array
    {
        $statement = $this->db->prepare(
            "SELECT listings.*, cards.card_name, cards.set_name, cards.collector_number, cards.image_url
             FROM listings INNER JOIN cards ON cards.id = listings.card_id
             WHERE listings.user_id = :user_id AND listings.status = 'active'
             ORDER BY listings.created_at DESC"
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function allForAdmin(): array
    {
        $sql = 'SELECT listings.*, cards.card_name, cards.set_name, cards.collector_number, users.username
                FROM listings
                INNER JOIN cards ON cards.id = listings.card_id
                INNER JOIN users ON users.id = listings.user_id
                ORDER BY listings.created_at DESC';

        $statement = $this->db->query($sql);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $sql = 'SELECT listings.*, cards.card_name, cards.set_name, cards.collector_number,
                       cards.rarity, cards.color, cards.type_line, users.username,
                       users.email_verified_at, users.account_status
                FROM listings
                INNER JOIN cards ON cards.id = listings.card_id
                INNER JOIN users ON users.id = listings.user_id
                WHERE listings.id = :id
                LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute(['id' => $id]);
        $listing = $statement->fetch();

        return $listing ?: null;
    }

    public function create(array $data): bool
    {
        $sql = 'INSERT INTO listings
                    (user_id, card_id, quantity, card_condition, price_php, seller_location, delivery_options, status, notes)
                VALUES
                    (:user_id, :card_id, :quantity, :card_condition, :price_php, :seller_location, :delivery_options, :status, :notes)';

        $statement = $this->db->prepare($sql);

        return $statement->execute($this->params($data));
    }

    public function update(int $id, array $data): bool
    {
        $sql = 'UPDATE listings SET
                    quantity = :quantity,
                    card_condition = :card_condition,
                    price_php = :price_php,
                    seller_location = :seller_location,
                    delivery_options = :delivery_options,
                    status = :status,
                    notes = :notes
                WHERE id = :id';

        $params = $this->params($data);
        $params['id'] = $id;
        unset($params['user_id'], $params['card_id']);

        $statement = $this->db->prepare($sql);

        return $statement->execute($params);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $statement = $this->db->prepare('UPDATE listings SET status = :status WHERE id = :id');

        return $statement->execute([
            'id' => $id,
            'status' => $status,
        ]);
    }

    public function countAll(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM listings')->fetchColumn();
    }

    public function countSuspiciousQueue(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM listings WHERE status IN ('active', 'reserved')")->fetchColumn();
    }

    private function params(array $data): array
    {
        return [
            'user_id' => $data['user_id'] ?? null,
            'card_id' => $data['card_id'] ?? null,
            'quantity' => (int) $data['quantity'],
            'card_condition' => $data['card_condition'],
            'price_php' => $data['price_php'],
            'seller_location' => $data['seller_location'],
            'delivery_options' => $data['delivery_options'],
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?: null,
        ];
    }

    private function appendMarketplaceFilters(string &$sql, array &$params, array $filters): void
    {
        if (!empty($filters['q'])) {
            $sql .= ' AND (cards.card_name LIKE :q_card_name OR cards.set_name LIKE :q_set_name)';
            $query = '%' . $filters['q'] . '%';
            $params['q_card_name'] = $query;
            $params['q_set_name'] = $query;
        }

        if (!empty($filters['exclude_user_id'])) {
            $sql .= ' AND listings.user_id <> :exclude_user_id';
            $params['exclude_user_id'] = (int) $filters['exclude_user_id'];
        }
    }
}
