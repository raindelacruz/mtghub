<?php

class Wallet
{
    private const TRANSACTION_TYPES = [
        'credit_admin_adjustment',
        'debit_admin_adjustment',
        'credit_trade_in',
        'credit_order_refund',
        'credit_order_settlement',
        'debit_order_refund_recovery',
        'debit_checkout_payment',
        'credit_promotion',
        'credit_buylist_settlement',
    ];

    public static function getOrCreateByUserId(int $userId): array
    {
        $db = Database::connection();
        $wallet = self::findByUserId($userId, false);

        if ($wallet !== null) {
            return $wallet;
        }

        $statement = $db->prepare('INSERT INTO wallets (user_id, store_credit_balance) VALUES (:user_id, 0.00)');
        $statement->execute(['user_id' => $userId]);

        return self::findByUserId($userId, false) ?: [];
    }

    public static function getBalance(int $userId): float
    {
        $wallet = self::getOrCreateByUserId($userId);

        return (float) $wallet['store_credit_balance'];
    }

    public static function credit(
        int $userId,
        float $amount,
        string $transactionType,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $createdBy = null,
        ?string $idempotencyKey = null
    ): array {
        return self::changeBalance($userId, $amount, 'credit', $transactionType, $referenceType, $referenceId, $notes, $createdBy, $idempotencyKey);
    }

    public static function debit(
        int $userId,
        float $amount,
        string $transactionType,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $createdBy = null,
        ?string $idempotencyKey = null
    ): array {
        return self::changeBalance($userId, $amount, 'debit', $transactionType, $referenceType, $referenceId, $notes, $createdBy, $idempotencyKey);
    }

    public static function transactionsByUser(int $userId): array
    {
        $db = Database::connection();
        $statement = $db->prepare(
            'SELECT wallet_transactions.*, users.username AS created_by_username
             FROM wallet_transactions
             LEFT JOIN users ON users.id = wallet_transactions.created_by
             WHERE wallet_transactions.user_id = :user_id
             ORDER BY wallet_transactions.created_at DESC, wallet_transactions.id DESC'
        );
        $statement->execute(['user_id' => $userId]);

        return $statement->fetchAll();
    }

    public static function adminAdjust(int $userId, float $amount, string $direction, string $notes, int $adminId): array
    {
        if ($direction === 'credit') {
            return self::credit($userId, $amount, 'credit_admin_adjustment', 'admin_adjustment', null, $notes, $adminId);
        }

        if ($direction === 'debit') {
            return self::debit($userId, $amount, 'debit_admin_adjustment', 'admin_adjustment', null, $notes, $adminId);
        }

        throw new InvalidArgumentException('Choose credit or debit for the adjustment.');
    }

    public static function usersWithWallets(string $query = ''): array
    {
        $db = Database::connection();
        $params = [];
        $where = '';

        if ($query !== '') {
            $where = 'WHERE users.username LIKE :query_username OR users.email LIKE :query_email';
            $like = '%' . $query . '%';
            $params = [
                'query_username' => $like,
                'query_email' => $like,
            ];
        }

        $statement = $db->prepare(
            "SELECT users.id, users.username, users.email, users.role, users.created_at,
                    COALESCE(wallets.store_credit_balance, 0.00) AS store_credit_balance
             FROM users
             LEFT JOIN wallets ON wallets.user_id = users.id
             $where
             ORDER BY users.username ASC"
        );
        $statement->execute($params);

        return $statement->fetchAll();
    }

    private static function changeBalance(
        int $userId,
        float $amount,
        string $direction,
        string $transactionType,
        ?string $referenceType,
        ?int $referenceId,
        ?string $notes,
        ?int $createdBy,
        ?string $idempotencyKey
    ): array {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Store credit amount must be greater than zero.');
        }

        if (!in_array($transactionType, self::TRANSACTION_TYPES, true)) {
            throw new InvalidArgumentException('Invalid wallet transaction type.');
        }

        $db = Database::connection();
        $startedTransaction = !$db->inTransaction();

        if ($startedTransaction) {
            $db->beginTransaction();
        }

        try {
            $wallet = self::findByUserId($userId, true);

            if ($wallet === null) {
                $statement = $db->prepare('INSERT INTO wallets (user_id, store_credit_balance) VALUES (:user_id, 0.00)');
                $statement->execute(['user_id' => $userId]);
                $wallet = self::findByUserId($userId, true);
            }

            if ($wallet === null) {
                throw new RuntimeException('Unable to create wallet.');
            }

            if ($idempotencyKey !== null) {
                if (mb_strlen($idempotencyKey) > 120) {
                    throw new InvalidArgumentException('Wallet idempotency key is too long.');
                }
                $existing = $db->prepare('SELECT id FROM wallet_transactions WHERE idempotency_key = :idempotency_key LIMIT 1');
                $existing->execute(['idempotency_key' => $idempotencyKey]);
                if ($existing->fetchColumn()) {
                    if ($startedTransaction) {
                        $db->commit();
                    }
                    return self::findByUserId($userId, false) ?: [];
                }
            }

            $balanceBefore = (float) $wallet['store_credit_balance'];
            $balanceAfter = $direction === 'credit' ? $balanceBefore + $amount : $balanceBefore - $amount;

            if ($balanceAfter < 0) {
                throw new RuntimeException('Store credit balance cannot go negative.');
            }

            $update = $db->prepare('UPDATE wallets SET store_credit_balance = :balance WHERE id = :id');
            $update->execute([
                'id' => (int) $wallet['id'],
                'balance' => number_format($balanceAfter, 2, '.', ''),
            ]);

            $ledger = $db->prepare('INSERT INTO wallet_transactions
                    (wallet_id, user_id, transaction_type, amount, balance_before, balance_after,
                     reference_type, reference_id, idempotency_key, notes, created_by)
                VALUES
                    (:wallet_id, :user_id, :transaction_type, :amount, :balance_before, :balance_after,
                     :reference_type, :reference_id, :idempotency_key, :notes, :created_by)');
            $ledger->execute([
                'wallet_id' => (int) $wallet['id'],
                'user_id' => $userId,
                'transaction_type' => $transactionType,
                'amount' => number_format($amount, 2, '.', ''),
                'balance_before' => number_format($balanceBefore, 2, '.', ''),
                'balance_after' => number_format($balanceAfter, 2, '.', ''),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'idempotency_key' => $idempotencyKey,
                'notes' => $notes ?: null,
                'created_by' => $createdBy,
            ]);

            if ($startedTransaction) {
                $db->commit();
            }

            return self::findByUserId($userId, false) ?: [];
        } catch (Throwable $exception) {
            if ($startedTransaction && $db->inTransaction()) {
                $db->rollBack();
            }

            throw $exception;
        }
    }

    private static function findByUserId(int $userId, bool $forUpdate): ?array
    {
        $db = Database::connection();
        $sql = 'SELECT * FROM wallets WHERE user_id = :user_id LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : '');
        $statement = $db->prepare($sql);
        $statement->execute(['user_id' => $userId]);
        $wallet = $statement->fetch();

        return $wallet ?: null;
    }
}
