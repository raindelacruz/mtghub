<?php

class EmailVerification
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function canRequest(int $userId): bool
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM email_verification_tokens
             WHERE user_id = :user_id AND created_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND)'
        );
        $statement->execute(['user_id' => $userId]);
        return (int) $statement->fetchColumn() === 0;
    }

    public function create(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->db->beginTransaction();
        try {
            $delete = $this->db->prepare('DELETE FROM email_verification_tokens WHERE user_id = :user_id');
            $delete->execute(['user_id' => $userId]);
            $insert = $this->db->prepare(
                'INSERT INTO email_verification_tokens (user_id, token_hash, expires_at)
                 VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 24 HOUR))'
            );
            $insert->execute(['user_id' => $userId, 'token_hash' => hash('sha256', $token)]);
            $this->db->commit();
            return $token;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function verify(string $token): ?int
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                'SELECT id, user_id FROM email_verification_tokens
                 WHERE token_hash = :token_hash AND expires_at > NOW() LIMIT 1 FOR UPDATE'
            );
            $statement->execute(['token_hash' => hash('sha256', $token)]);
            $record = $statement->fetch();
            if (!$record) {
                $this->db->rollBack();
                return null;
            }

            $update = $this->db->prepare(
                "UPDATE users SET email_verified_at = NOW(), account_status = IF(account_status = 'pending', 'active', account_status)
                 WHERE id = :id"
            );
            $update->execute(['id' => (int) $record['user_id']]);
            $delete = $this->db->prepare('DELETE FROM email_verification_tokens WHERE user_id = :user_id');
            $delete->execute(['user_id' => (int) $record['user_id']]);
            $this->db->commit();
            return (int) $record['user_id'];
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
