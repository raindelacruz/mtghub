<?php

class LoginAttempt
{
    private const WINDOW_SECONDS = 900;
    private const MAX_ACCOUNT_IP_ATTEMPTS = 5;
    private const MAX_IP_ATTEMPTS = 25;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function isBlocked(string $email, string $ipAddress): bool
    {
        $this->cleanupOccasionally();
        $emailHash = hash('sha256', mb_strtolower(trim($email)));
        $ipHash = hash('sha256', $ipAddress);

        $statement = $this->db->prepare(
            'SELECT
                SUM(email_hash = :email_hash AND ip_hash = :ip_hash_pair) AS pair_attempts,
                SUM(ip_hash = :ip_hash_total) AS ip_attempts
             FROM login_attempts
             WHERE attempted_at >= DATE_SUB(NOW(), INTERVAL ' . self::WINDOW_SECONDS . ' SECOND)'
        );
        $statement->execute([
            'email_hash' => $emailHash,
            'ip_hash_pair' => $ipHash,
            'ip_hash_total' => $ipHash,
        ]);
        $counts = $statement->fetch() ?: [];

        return (int) ($counts['pair_attempts'] ?? 0) >= self::MAX_ACCOUNT_IP_ATTEMPTS
            || (int) ($counts['ip_attempts'] ?? 0) >= self::MAX_IP_ATTEMPTS;
    }

    public function recordFailure(string $email, string $ipAddress): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO login_attempts (email_hash, ip_hash) VALUES (:email_hash, :ip_hash)'
        );
        $statement->execute([
            'email_hash' => hash('sha256', mb_strtolower(trim($email))),
            'ip_hash' => hash('sha256', $ipAddress),
        ]);
    }

    public function clear(string $email, string $ipAddress): void
    {
        $statement = $this->db->prepare(
            'DELETE FROM login_attempts WHERE email_hash = :email_hash AND ip_hash = :ip_hash'
        );
        $statement->execute([
            'email_hash' => hash('sha256', mb_strtolower(trim($email))),
            'ip_hash' => hash('sha256', $ipAddress),
        ]);
    }

    private function cleanupOccasionally(): void
    {
        if (random_int(1, 100) !== 1) {
            return;
        }

        $this->db->exec('DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');
    }
}
