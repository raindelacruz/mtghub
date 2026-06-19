USE mtghub;

CREATE TABLE IF NOT EXISTS wallets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    store_credit_balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_wallet_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wallet_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wallet_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    transaction_type ENUM(
        'credit_admin_adjustment',
        'debit_admin_adjustment',
        'credit_trade_in',
        'credit_order_refund',
        'credit_order_settlement',
        'debit_checkout_payment',
        'credit_promotion',
        'credit_buylist_settlement'
    ) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    balance_before DECIMAL(10, 2) NOT NULL,
    balance_after DECIMAL(10, 2) NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id INT UNSIGNED NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wallet_tx_wallet (wallet_id),
    INDEX idx_wallet_tx_user (user_id),
    INDEX idx_wallet_tx_reference (reference_type, reference_id),
    CONSTRAINT fk_wallet_tx_wallet FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
    CONSTRAINT fk_wallet_tx_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_wallet_tx_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS store_credit_used DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS cash_amount_due DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS store_credit_refunded TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS store_credit_settled TINYINT(1) NOT NULL DEFAULT 0;

UPDATE orders
SET payment_method = 'cash_gcach_bank'
WHERE payment_method NOT IN ('cash_gcach_bank', 'store_credit', 'mixed');

ALTER TABLE orders
    MODIFY payment_method ENUM('cash_gcach_bank', 'store_credit', 'mixed') NOT NULL DEFAULT 'cash_gcach_bank';

UPDATE orders
SET cash_amount_due = total_price_php
WHERE cash_amount_due = 0.00 AND store_credit_used = 0.00;
