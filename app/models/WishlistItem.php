<?php

class WishlistItem
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function forUser(int $userId): array
    {
        $sql = 'SELECT wishlist_items.*, cards.card_name, cards.set_name, cards.collector_number,
                       cards.rarity, cards.color, cards.type_line
                FROM wishlist_items
                INNER JOIN cards ON cards.id = wishlist_items.card_id
                WHERE wishlist_items.user_id = :user_id
                ORDER BY cards.card_name ASC, cards.set_name ASC';

        $statement = $this->db->prepare($sql);
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public function allForAdmin(): array
    {
        $sql = 'SELECT wishlist_items.*, cards.card_name, cards.set_name, cards.collector_number,
                       users.username, users.email
                FROM wishlist_items
                INNER JOIN cards ON cards.id = wishlist_items.card_id
                INNER JOIN users ON users.id = wishlist_items.user_id
                ORDER BY wishlist_items.created_at DESC, cards.card_name ASC';

        return $this->db->query($sql)->fetchAll();
    }

    public function findForUser(int $id, int $userId): ?array
    {
        $sql = 'SELECT wishlist_items.*, cards.card_name, cards.set_name, cards.collector_number,
                       cards.rarity, cards.color, cards.type_line
                FROM wishlist_items
                INNER JOIN cards ON cards.id = wishlist_items.card_id
                WHERE wishlist_items.id = :id AND wishlist_items.user_id = :user_id
                LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);
        $item = $statement->fetch();

        return $item ?: null;
    }

    public function find(int $id): ?array
    {
        $sql = 'SELECT wishlist_items.*, cards.card_name, cards.set_name, cards.collector_number,
                       cards.rarity, cards.color, cards.type_line
                FROM wishlist_items
                INNER JOIN cards ON cards.id = wishlist_items.card_id
                WHERE wishlist_items.id = :id
                LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute(['id' => $id]);
        $item = $statement->fetch();

        return $item ?: null;
    }

    public function existsForUserAndCard(int $userId, int $cardId): bool
    {
        $statement = $this->db->prepare(
            'SELECT id FROM wishlist_items WHERE user_id = :user_id AND card_id = :card_id LIMIT 1'
        );
        $statement->execute([
            'user_id' => $userId,
            'card_id' => $cardId,
        ]);

        return (bool) $statement->fetch();
    }

    public function create(array $data): bool
    {
        $sql = 'INSERT INTO wishlist_items (user_id, card_id, desired_quantity, max_price_php, notes)
                VALUES (:user_id, :card_id, :desired_quantity, :max_price_php, :notes)';

        $statement = $this->db->prepare($sql);

        return $statement->execute($this->params($data));
    }

    public function update(int $id, int $userId, array $data): bool
    {
        $sql = 'UPDATE wishlist_items SET
                    desired_quantity = :desired_quantity,
                    max_price_php = :max_price_php,
                    notes = :notes
                WHERE id = :id AND user_id = :user_id';

        $params = $this->params($data);
        $params['id'] = $id;
        $params['user_id'] = $userId;
        unset($params['card_id']);

        $statement = $this->db->prepare($sql);

        return $statement->execute($params);
    }

    public function delete(int $id, int $userId): bool
    {
        $statement = $this->db->prepare('DELETE FROM wishlist_items WHERE id = :id AND user_id = :user_id');

        return $statement->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    public function matchesForUser(int $userId): array
    {
        $sql = 'SELECT wishlist_items.desired_quantity, wishlist_items.max_price_php,
                       listing_cards.card_name, listing_cards.set_name, listing_cards.collector_number,
                       listings.id AS listing_id, listings.quantity AS listing_quantity,
                       listings.card_condition, listings.price_php, listings.seller_location,
                       listings.delivery_options, listings.status, users.username
                FROM wishlist_items
                INNER JOIN cards AS buylist_cards ON buylist_cards.id = wishlist_items.card_id
                INNER JOIN cards AS listing_cards ON LOWER(TRIM(listing_cards.card_name)) = LOWER(TRIM(buylist_cards.card_name))
                INNER JOIN listings ON listings.card_id = listing_cards.id
                INNER JOIN users ON users.id = listings.user_id
                WHERE wishlist_items.user_id = :user_id
                  AND listings.status = :status
                  AND listings.user_id <> :seller_user_id
                  AND (wishlist_items.max_price_php IS NULL OR listings.price_php <= wishlist_items.max_price_php)
                ORDER BY listing_cards.card_name ASC, listings.price_php ASC';

        $statement = $this->db->prepare($sql);
        $statement->execute([
            'user_id' => $userId,
            'seller_user_id' => $userId,
            'status' => 'active',
        ]);

        return $statement->fetchAll();
    }

    public function demandForSeller(int $sellerId): array
    {
        $sql = 'SELECT wishlist_items.id AS wishlist_item_id, wishlist_items.user_id AS buyer_id,
                       wishlist_items.desired_quantity, wishlist_items.max_price_php, wishlist_items.notes AS buyer_notes,
                       listing_cards.card_name, listing_cards.set_name, listing_cards.collector_number,
                       listings.id AS listing_id, listings.quantity AS listing_quantity,
                       listings.card_condition, listings.price_php, listings.status AS listing_status,
                       users.username AS buyer_username, users.city AS buyer_city, users.province AS buyer_province
                FROM listings
                INNER JOIN cards AS listing_cards ON listing_cards.id = listings.card_id
                INNER JOIN cards AS buylist_cards ON LOWER(TRIM(buylist_cards.card_name)) = LOWER(TRIM(listing_cards.card_name))
                INNER JOIN wishlist_items ON wishlist_items.card_id = buylist_cards.id
                INNER JOIN users ON users.id = wishlist_items.user_id
                LEFT JOIN buylist_offers ON buylist_offers.listing_id = listings.id
                    AND buylist_offers.wishlist_item_id = wishlist_items.id
                    AND buylist_offers.status = :pending_status
                WHERE listings.user_id = :seller_id
                  AND listings.status = :listing_status
                  AND listings.quantity > 0
                  AND wishlist_items.user_id <> :buyer_user_id
                  AND buylist_offers.id IS NULL
                ORDER BY listing_cards.card_name ASC, listings.price_php ASC, users.username ASC';

        $statement = $this->db->prepare($sql);
        $statement->execute([
            'seller_id' => $sellerId,
            'buyer_user_id' => $sellerId,
            'listing_status' => 'active',
            'pending_status' => 'pending',
        ]);

        return $statement->fetchAll();
    }

    public function demandCountForCard(int $cardId): int
    {
        $statement = $this->db->prepare('SELECT COALESCE(SUM(desired_quantity), 0) FROM wishlist_items WHERE card_id = :card_id');
        $statement->execute(['card_id' => $cardId]);

        return (int) $statement->fetchColumn();
    }

    private function params(array $data): array
    {
        return [
            'user_id' => $data['user_id'] ?? null,
            'card_id' => $data['card_id'] ?? null,
            'desired_quantity' => (int) $data['desired_quantity'],
            'max_price_php' => $data['max_price_php'] === '' ? null : $data['max_price_php'],
            'notes' => $data['notes'] ?: null,
        ];
    }
}
