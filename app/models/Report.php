<?php

class Report
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(array $data): bool
    {
        $statement = $this->db->prepare(
            'INSERT INTO reports (reporter_id, subject_type, subject_id, reason, details)
             VALUES (:reporter_id, :subject_type, :subject_id, :reason, :details)'
        );
        return $statement->execute($data);
    }

    public function hasRecentDuplicate(int $reporterId, string $type, int $subjectId): bool
    {
        $statement = $this->db->prepare(
            "SELECT COUNT(*) FROM reports WHERE reporter_id = :reporter_id AND subject_type = :subject_type
             AND subject_id = :subject_id AND status IN ('open','reviewing')"
        );
        $statement->execute(['reporter_id' => $reporterId, 'subject_type' => $type, 'subject_id' => $subjectId]);
        return (int) $statement->fetchColumn() > 0;
    }

    public function allForAdmin(): array
    {
        return $this->db->query(
            "SELECT reports.*, reporter.username AS reporter_username, reviewer.username AS reviewer_username,
                    CASE WHEN reports.subject_type = 'user' THEN subject_user.username WHEN reports.subject_type = 'listing' THEN cards.card_name ELSE CONCAT('Review #', subject_review.id, ' for ', review_seller.username) END AS subject_label
             FROM reports
             INNER JOIN users reporter ON reporter.id = reports.reporter_id
             LEFT JOIN users reviewer ON reviewer.id = reports.reviewed_by
             LEFT JOIN users subject_user ON reports.subject_type = 'user' AND subject_user.id = reports.subject_id
             LEFT JOIN listings ON reports.subject_type = 'listing' AND listings.id = reports.subject_id
             LEFT JOIN cards ON cards.id = listings.card_id
             LEFT JOIN marketplace_reviews subject_review ON reports.subject_type = 'review' AND subject_review.id = reports.subject_id
             LEFT JOIN users review_seller ON review_seller.id = subject_review.seller_id
             ORDER BY FIELD(reports.status, 'open','reviewing','resolved','dismissed'), reports.created_at DESC"
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM reports WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function updateStatus(int $id, string $status, string $notes, int $adminId): bool
    {
        $statement = $this->db->prepare(
            'UPDATE reports SET status = :status, resolution_notes = :notes, reviewed_by = :reviewed_by,
             reviewed_at = NOW() WHERE id = :id'
        );
        return $statement->execute(['id' => $id, 'status' => $status, 'notes' => $notes ?: null, 'reviewed_by' => $adminId]);
    }

    public function countOpen(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM reports WHERE status IN ('open','reviewing')")->fetchColumn();
    }
}
