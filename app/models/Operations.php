<?php

class Operations
{
    private PDO $db;
    public function __construct() { $this->db = Database::connection(); }
    public function snapshot(): array
    {
        $scalar = fn (string $sql): int => (int) $this->db->query($sql)->fetchColumn();
        return [
            'critical_24h' => $scalar("SELECT COUNT(*) FROM system_events WHERE severity='critical' AND resolved_at IS NULL AND occurred_at>=NOW()-INTERVAL 24 HOUR"),
            'errors_24h' => $scalar("SELECT COUNT(*) FROM system_events WHERE severity IN ('error','critical') AND resolved_at IS NULL AND occurred_at>=NOW()-INTERVAL 24 HOUR"),
            'pending_payments' => $scalar("SELECT COUNT(*) FROM orders WHERE status IN ('pending_payment','payment_submitted')"),
            'open_disputes' => $scalar("SELECT COUNT(*) FROM order_disputes WHERE status IN ('open','reviewing')"),
            'pending_deletions' => $scalar("SELECT COUNT(*) FROM account_deletion_requests WHERE status='pending'"),
            'last_backup' => $this->db->query("SELECT * FROM backup_runs WHERE status IN ('completed','restored') ORDER BY completed_at DESC LIMIT 1")->fetch() ?: null,
            'recent_events' => $this->db->query('SELECT * FROM system_events ORDER BY occurred_at DESC LIMIT 10')->fetchAll(),
            'migrations' => $this->db->query('SELECT * FROM schema_migrations ORDER BY applied_at DESC')->fetchAll(),
        ];
    }

    public function resolveEvent(int $id, string $resolution, int $adminId): void
    {
        $statement=$this->db->prepare('UPDATE system_events SET resolved_at=NOW(),resolution=?,resolved_by=? WHERE id=? AND resolved_at IS NULL');
        $statement->execute([$resolution,$adminId,$id]);
    }
}
