USE mtghub;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS store_credit_refunded_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER store_credit_refunded;
UPDATE orders SET store_credit_refunded_amount = store_credit_used WHERE store_credit_refunded = 1 AND store_credit_refunded_amount = 0;

ALTER TABLE wallet_transactions MODIFY COLUMN transaction_type ENUM(
    'credit_admin_adjustment','debit_admin_adjustment','credit_trade_in','credit_order_refund',
    'credit_order_settlement','debit_order_refund_recovery','debit_checkout_payment',
    'credit_promotion','credit_buylist_settlement'
) NOT NULL;

CREATE TABLE IF NOT EXISTS order_disputes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    opened_by INT UNSIGNED NOT NULL,
    reason ENUM('non_payment','non_delivery','wrong_card','condition_mismatch','counterfeit_concern','other') NOT NULL,
    details TEXT NOT NULL,
    evidence_notes TEXT NULL,
    order_status_before VARCHAR(30) NOT NULL,
    status ENUM('open','reviewing','resolved') NOT NULL DEFAULT 'open',
    resolution ENUM('full_refund','partial_refund','denied','no_action') NULL,
    refund_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    refund_store_credit DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    refund_external DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    resolution_notes TEXT NULL,
    resolved_by INT UNSIGNED NULL,
    resolved_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_disputes_status_created (status, created_at),
    INDEX idx_disputes_order (order_id),
    CONSTRAINT fk_dispute_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_dispute_opener FOREIGN KEY (opened_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_dispute_resolver FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketplace_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL UNIQUE,
    reviewer_id INT UNSIGNED NOT NULL,
    seller_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    body VARCHAR(2000) NOT NULL,
    status ENUM('published','hidden') NOT NULL DEFAULT 'published',
    moderation_notes VARCHAR(1000) NULL,
    moderated_by INT UNSIGNED NULL,
    moderated_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reviews_seller_status (seller_id, status, created_at),
    CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_review_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_review_moderator FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE reports MODIFY COLUMN subject_type ENUM('user','listing','review') NOT NULL;
