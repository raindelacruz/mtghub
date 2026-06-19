<?php

class MarketplaceReview
{
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }

    public function create(int $orderId, int $buyerId, int $rating, string $body): int
    {
        $statement = $this->db->prepare("INSERT INTO marketplace_reviews (order_id,reviewer_id,seller_id,rating,body) SELECT orders.id,:buyer_id,orders.seller_id,:rating,:body FROM orders WHERE orders.id=:order_id AND orders.buyer_id=:buyer_check AND orders.status='completed' AND NOT EXISTS (SELECT 1 FROM marketplace_reviews WHERE marketplace_reviews.order_id=orders.id)");
        $statement->execute(['buyer_id' => $buyerId, 'rating' => $rating, 'body' => $body, 'order_id' => $orderId, 'buyer_check' => $buyerId]);
        if ($statement->rowCount() !== 1) throw new RuntimeException('Only the buyer can review a completed order once.');
        return (int) $this->db->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT marketplace_reviews.*, reviewer.username AS reviewer_username, seller.username AS seller_username FROM marketplace_reviews INNER JOIN users reviewer ON reviewer.id=marketplace_reviews.reviewer_id INNER JOIN users seller ON seller.id=marketplace_reviews.seller_id WHERE marketplace_reviews.id=:id LIMIT 1');
        $statement->execute(['id' => $id]); return $statement->fetch() ?: null;
    }

    public function findForOrder(int $orderId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM marketplace_reviews WHERE order_id=:order_id LIMIT 1'); $statement->execute(['order_id' => $orderId]); return $statement->fetch() ?: null;
    }

    public function forSeller(int $sellerId): array
    {
        $statement = $this->db->prepare("SELECT marketplace_reviews.*, users.username AS reviewer_username FROM marketplace_reviews INNER JOIN users ON users.id=marketplace_reviews.reviewer_id WHERE seller_id=:seller_id AND status='published' ORDER BY created_at DESC");
        $statement->execute(['seller_id' => $sellerId]); return $statement->fetchAll();
    }

    public function metrics(int $sellerId): array
    {
        $statement = $this->db->prepare("SELECT COALESCE(AVG(CASE WHEN marketplace_reviews.status='published' THEN marketplace_reviews.rating END),0) AS average_rating, COUNT(CASE WHEN marketplace_reviews.status='published' THEN 1 END) AS review_count, (SELECT COUNT(*) FROM orders WHERE seller_id=:seller_orders AND status='completed') AS completed_sales, (SELECT COUNT(*) FROM orders WHERE seller_id=:seller_failed AND status IN ('cancelled','expired','refunded','disputed')) AS problem_orders, (SELECT COUNT(*) FROM orders WHERE seller_id=:seller_total AND status IN ('completed','cancelled','expired','refunded','disputed')) AS closed_orders FROM marketplace_reviews WHERE seller_id=:seller_reviews");
        $statement->execute(['seller_orders'=>$sellerId,'seller_failed'=>$sellerId,'seller_total'=>$sellerId,'seller_reviews'=>$sellerId]);
        $metrics = $statement->fetch() ?: [];
        $closed=(int)($metrics['closed_orders']??0); $metrics['completion_rate']=$closed>0?round(((int)$metrics['completed_sales']/$closed)*100,1):100.0;
        return $metrics;
    }

    public function allForAdmin(): array
    {
        return $this->db->query('SELECT marketplace_reviews.*, reviewer.username AS reviewer_username, seller.username AS seller_username FROM marketplace_reviews INNER JOIN users reviewer ON reviewer.id=marketplace_reviews.reviewer_id INNER JOIN users seller ON seller.id=marketplace_reviews.seller_id ORDER BY created_at DESC')->fetchAll();
    }

    public function moderate(int $id, string $status, string $notes, int $adminId): void
    {
        $statement=$this->db->prepare('UPDATE marketplace_reviews SET status=:status,moderation_notes=:notes,moderated_by=:admin,moderated_at=NOW() WHERE id=:id');
        $statement->execute(['status'=>$status,'notes'=>$notes?:null,'admin'=>$adminId,'id'=>$id]);
    }
}
