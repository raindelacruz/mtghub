USE mtghub;

ALTER TABLE orders MODIFY COLUMN status VARCHAR(30) NOT NULL DEFAULT 'pending_payment';

UPDATE orders SET status = CASE status
    WHEN 'pending' THEN 'pending_payment'
    WHEN 'confirmed' THEN 'payment_verified'
    ELSE status
END;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS external_payment_method VARCHAR(80) NOT NULL DEFAULT '' AFTER payment_method,
    ADD COLUMN IF NOT EXISTS payment_deadline DATETIME NULL AFTER payment_reference,
    ADD COLUMN IF NOT EXISTS tracking_carrier VARCHAR(80) NULL AFTER payment_deadline,
    ADD COLUMN IF NOT EXISTS tracking_reference VARCHAR(150) NULL AFTER tracking_carrier,
    ADD COLUMN IF NOT EXISTS shipped_at DATETIME NULL AFTER tracking_reference,
    ADD COLUMN IF NOT EXISTS delivered_at DATETIME NULL AFTER shipped_at,
    ADD COLUMN IF NOT EXISTS completed_at DATETIME NULL AFTER delivered_at,
    ADD COLUMN IF NOT EXISTS cancelled_at DATETIME NULL AFTER completed_at;

UPDATE orders
SET payment_deadline = COALESCE(payment_deadline, DATE_ADD(created_at, INTERVAL 24 HOUR)),
    completed_at = IF(status = 'completed', COALESCE(completed_at, updated_at), completed_at),
    cancelled_at = IF(status = 'cancelled', COALESCE(cancelled_at, updated_at), cancelled_at);

ALTER TABLE orders MODIFY COLUMN status ENUM(
    'pending_payment','payment_submitted','payment_verified','preparing','shipped',
    'ready_for_meetup','delivered','buyer_confirmed','completed','cancelled','expired',
    'disputed','refunded'
) NOT NULL DEFAULT 'pending_payment';

CREATE TABLE IF NOT EXISTS order_status_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    from_status VARCHAR(30) NULL,
    to_status VARCHAR(30) NOT NULL,
    changed_by INT UNSIGNED NULL,
    note VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_history_order_created (order_id, created_at),
    CONSTRAINT fk_order_history_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_history_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO order_status_history (order_id, from_status, to_status, changed_by, note, created_at)
SELECT orders.id, NULL, orders.status, NULL, 'Lifecycle migration', orders.created_at
FROM orders
WHERE NOT EXISTS (SELECT 1 FROM order_status_history WHERE order_status_history.order_id = orders.id);
