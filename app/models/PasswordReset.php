<?php

class PasswordReset
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function create(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $ttl = max(300, min((int) PASSWORD_RESET_TTL, 86400));

        $this->db->beginTransaction();
        try {
            $delete = $this->db->prepare('DELETE FROM password_resets WHERE user_id = :user_id');
            $delete->execute(['user_id' => $userId]);

            $insert = $this->db->prepare(
                'INSERT INTO password_resets (user_id, token_hash, expires_at)
                 VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL ' . $ttl . ' SECOND))'
            );
            $insert->execute([
                'user_id' => $userId,
                'token_hash' => $tokenHash,
            ]);
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return $token;
    }

    public function canRequest(int $userId): bool
    {
        $statement = $this->db->prepare(
            'SELECT COUNT(*) FROM password_resets
             WHERE user_id = :user_id AND created_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND)'
        );
        $statement->execute(['user_id' => $userId]);
        return (int) $statement->fetchColumn() === 0;
    }

    public function findValid(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $statement = $this->db->prepare(
            'SELECT password_resets.*, users.email
             FROM password_resets
             INNER JOIN users ON users.id = password_resets.user_id
             WHERE password_resets.token_hash = :token_hash
               AND password_resets.expires_at > NOW()
             LIMIT 1'
        );
        $statement->execute(['token_hash' => hash('sha256', $token)]);
        $reset = $statement->fetch();

        return $reset ?: null;
    }

    public function consume(string $token, string $passwordHash): bool
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                'SELECT id, user_id FROM password_resets
                 WHERE token_hash = :token_hash AND expires_at > NOW()
                 LIMIT 1 FOR UPDATE'
            );
            $statement->execute(['token_hash' => hash('sha256', $token)]);
            $reset = $statement->fetch();

            if (!$reset) {
                $this->db->rollBack();
                return false;
            }

            $update = $this->db->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
            $update->execute(['password_hash' => $passwordHash, 'id' => (int) $reset['user_id']]);

            $delete = $this->db->prepare('DELETE FROM password_resets WHERE user_id = :user_id');
            $delete->execute(['user_id' => (int) $reset['user_id']]);
            $this->db->commit();
            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
