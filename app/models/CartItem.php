<?php

class CartItem
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function forBuyer(int $buyerId): array
    {
        $sql = 'SELECT cart_items.*, listings.user_id AS seller_id, listings.card_id,
                       listings.quantity AS available_quantity, listings.card_condition,
                       listings.price_php, listings.seller_location, listings.delivery_options,
                       listings.status AS listing_status, cards.card_name, cards.set_name,
                       cards.collector_number, users.username AS seller_username
                FROM cart_items
                INNER JOIN listings ON listings.id = cart_items.listing_id
                INNER JOIN cards ON cards.id = listings.card_id
                INNER JOIN users ON users.id = listings.user_id
                WHERE cart_items.buyer_id = :buyer_id
                ORDER BY users.username ASC, cart_items.created_at ASC';

        $statement = $this->db->prepare($sql);
        $statement->execute(['buyer_id' => $buyerId]);

        return $statement->fetchAll();
    }

    public function add(int $buyerId, int $listingId, int $quantity): bool
    {
        $sql = 'INSERT INTO cart_items (buyer_id, listing_id, quantity)
                VALUES (:buyer_id, :listing_id, :quantity)
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)';

        $statement = $this->db->prepare($sql);

        return $statement->execute([
            'buyer_id' => $buyerId,
            'listing_id' => $listingId,
            'quantity' => $quantity,
        ]);
    }

    public function quantityForListing(int $buyerId, int $listingId): int
    {
        $statement = $this->db->prepare('SELECT quantity FROM cart_items WHERE buyer_id = :buyer_id AND listing_id = :listing_id LIMIT 1');
        $statement->execute([
            'buyer_id' => $buyerId,
            'listing_id' => $listingId,
        ]);

        return (int) ($statement->fetchColumn() ?: 0);
    }

    public function findForBuyerWithListing(int $buyerId, int $cartItemId): ?array
    {
        $sql = 'SELECT cart_items.*, listings.quantity AS available_quantity,
                       listings.status AS listing_status, cards.card_name
                FROM cart_items
                INNER JOIN listings ON listings.id = cart_items.listing_id
                INNER JOIN cards ON cards.id = listings.card_id
                WHERE cart_items.id = :id AND cart_items.buyer_id = :buyer_id
                LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute([
            'id' => $cartItemId,
            'buyer_id' => $buyerId,
        ]);
        $item = $statement->fetch();

        return $item ?: null;
    }

    public function updateQuantity(int $buyerId, int $cartItemId, int $quantity): bool
    {
        $statement = $this->db->prepare('UPDATE cart_items SET quantity = :quantity WHERE id = :id AND buyer_id = :buyer_id');

        return $statement->execute([
            'id' => $cartItemId,
            'buyer_id' => $buyerId,
            'quantity' => $quantity,
        ]);
    }

    public function delete(int $buyerId, int $cartItemId): bool
    {
        $statement = $this->db->prepare('DELETE FROM cart_items WHERE id = :id AND buyer_id = :buyer_id');

        return $statement->execute([
            'id' => $cartItemId,
            'buyer_id' => $buyerId,
        ]);
    }

    public function clear(int $buyerId): bool
    {
        $statement = $this->db->prepare('DELETE FROM cart_items WHERE buyer_id = :buyer_id');

        return $statement->execute(['buyer_id' => $buyerId]);
    }

    public function countForBuyer(int $buyerId): int
    {
        $statement = $this->db->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE buyer_id = :buyer_id');
        $statement->execute(['buyer_id' => $buyerId]);

        return (int) $statement->fetchColumn();
    }
}
