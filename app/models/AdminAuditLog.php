<?php

class AdminAuditLog
{
    public static function record(string $action, string $targetType, ?int $targetId, array $metadata = []): void
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO admin_audit_logs (admin_id, action, target_type, target_id, metadata_json, ip_hash)
             VALUES (:admin_id, :action, :target_type, :target_id, :metadata_json, :ip_hash)'
        );
        $statement->execute([
            'admin_id' => (int) current_user()['id'],
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'metadata_json' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_SLASHES),
            'ip_hash' => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown'),
        ]);
    }

    public static function recent(int $limit = 200): array
    {
        $statement = Database::connection()->prepare(
            'SELECT admin_audit_logs.*, users.username AS admin_username
             FROM admin_audit_logs INNER JOIN users ON users.id = admin_audit_logs.admin_id
             ORDER BY admin_audit_logs.created_at DESC LIMIT :limit'
        );
        $statement->bindValue(':limit', max(1, min($limit, 500)), PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }
}
