<?php

class BuylistOffer
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function forBuyer(int $buyerId): array
    {
        $sql = 'SELECT buylist_offers.*, listings.card_id, listings.quantity AS listing_quantity,
                       listings.card_condition, listings.price_php, listings.seller_location,
                       listings.delivery_options, listings.status AS listing_status,
                       cards.card_name, cards.set_name, cards.collector_number,
                       users.username AS seller_username
                FROM buylist_offers
                INNER JOIN listings ON listings.id = buylist_offers.listing_id
                INNER JOIN cards ON cards.id = listings.card_id
                INNER JOIN users ON users.id = buylist_offers.seller_id
                WHERE buylist_offers.buyer_id = :buyer_id
                ORDER BY (buylist_offers.status = :pending_status) DESC, buylist_offers.created_at DESC';

        $statement = $this->db->prepare($sql);
        $statement->execute([
            'buyer_id' => $buyerId,
            'pending_status' => 'pending',
        ]);

        return $statement->fetchAll();
    }

    public function findForBuyer(int $id, int $buyerId): ?array
    {
        $sql = 'SELECT buylist_offers.*, listings.quantity AS listing_quantity,
                       listings.status AS listing_status, listings.user_id AS listing_seller_id,
                       sellers.account_status AS seller_account_status, sellers.email_verified_at AS seller_email_verified_at,
                       listings.price_php, wishlist_items.max_price_php
                FROM buylist_offers
                INNER JOIN listings ON listings.id = buylist_offers.listing_id
                INNER JOIN users sellers ON sellers.id = buylist_offers.seller_id
                INNER JOIN wishlist_items ON wishlist_items.id = buylist_offers.wishlist_item_id
                WHERE buylist_offers.id = :id AND buylist_offers.buyer_id = :buyer_id
                LIMIT 1';

        $statement = $this->db->prepare($sql);
        $statement->execute([
            'id' => $id,
            'buyer_id' => $buyerId,
        ]);
        $offer = $statement->fetch();

        return $offer ?: null;
    }

    public function create(array $data): bool
    {
        $sql = 'INSERT INTO buylist_offers
                    (buyer_id, seller_id, listing_id, wishlist_item_id, quantity, message, status)
                VALUES
                    (:buyer_id, :seller_id, :listing_id, :wishlist_item_id, :quantity, :message, :status)';

        $statement = $this->db->prepare($sql);

        return $statement->execute([
            'buyer_id' => $data['buyer_id'],
            'seller_id' => $data['seller_id'],
            'listing_id' => $data['listing_id'],
            'wishlist_item_id' => $data['wishlist_item_id'],
            'quantity' => $data['quantity'],
            'message' => $data['message'] ?: null,
            'status' => $data['status'] ?? 'pending',
        ]);
    }

    public function hasPendingForListingAndBuylist(int $listingId, int $wishlistItemId): bool
    {
        $statement = $this->db->prepare(
            'SELECT id FROM buylist_offers
             WHERE listing_id = :listing_id AND wishlist_item_id = :wishlist_item_id AND status = :status
             LIMIT 1'
        );
        $statement->execute([
            'listing_id' => $listingId,
            'wishlist_item_id' => $wishlistItemId,
            'status' => 'pending',
        ]);

        return (bool) $statement->fetch();
    }

    public function updateStatus(int $id, int $buyerId, string $status): bool
    {
        $statement = $this->db->prepare(
            'UPDATE buylist_offers SET status = :status WHERE id = :id AND buyer_id = :buyer_id'
        );

        return $statement->execute([
            'id' => $id,
            'buyer_id' => $buyerId,
            'status' => $status,
        ]);
    }
}
