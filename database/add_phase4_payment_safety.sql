USE mtghub;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS payment_status ENUM('pending','submitted','verified','rejected','refunded') NOT NULL DEFAULT 'pending' AFTER payment_deadline,
    ADD COLUMN IF NOT EXISTS fulfillment_status ENUM('awaiting_payment','awaiting_fulfillment','preparing','shipped','ready_for_meetup','delivered','completed','cancelled') NOT NULL DEFAULT 'awaiting_payment' AFTER payment_status,
    ADD COLUMN IF NOT EXISTS settlement_available_at DATETIME NULL AFTER fulfillment_status,
    ADD COLUMN IF NOT EXISTS settled_at DATETIME NULL AFTER settlement_available_at;

UPDATE orders SET
    payment_status = CASE WHEN status = 'pending_payment' THEN 'pending' WHEN status = 'payment_submitted' THEN 'submitted' WHEN status IN ('cancelled','expired') AND store_credit_refunded = 1 THEN 'refunded' ELSE 'verified' END,
    fulfillment_status = CASE WHEN status IN ('pending_payment','payment_submitted') THEN 'awaiting_payment' WHEN status = 'payment_verified' THEN 'awaiting_fulfillment' WHEN status = 'preparing' THEN 'preparing' WHEN status = 'shipped' THEN 'shipped' WHEN status = 'ready_for_meetup' THEN 'ready_for_meetup' WHEN status IN ('delivered','buyer_confirmed') THEN 'delivered' WHEN status = 'completed' THEN 'completed' WHEN status IN ('cancelled','expired','refunded') THEN 'cancelled' ELSE fulfillment_status END,
    settled_at = IF(store_credit_settled = 1, COALESCE(settled_at, completed_at, updated_at), settled_at);

ALTER TABLE wallet_transactions
    ADD COLUMN IF NOT EXISTS idempotency_key VARCHAR(120) NULL AFTER reference_id,
    ADD UNIQUE INDEX IF NOT EXISTS uq_wallet_transaction_idempotency (idempotency_key);

CREATE TABLE IF NOT EXISTS payment_proofs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(100) NOT NULL UNIQUE,
    mime_type ENUM('image/jpeg','image/png','image/webp') NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    image_width INT UNSIGNED NOT NULL,
    image_height INT UNSIGNED NOT NULL,
    sha256 CHAR(64) NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    review_notes VARCHAR(1000) NULL,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_proof_order_created (order_id, created_at),
    INDEX idx_payment_proof_status (status),
    CONSTRAINT fk_payment_proof_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_proof_uploader FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payment_proof_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
