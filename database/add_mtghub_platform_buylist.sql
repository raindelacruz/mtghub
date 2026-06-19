USE mtghub;

CREATE TABLE IF NOT EXISTS mtghub_buylist_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_id INT UNSIGNED NOT NULL,
    set_name VARCHAR(255) NULL,
    accepted_condition VARCHAR(50) NULL,
    cash_offer DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    credit_offer DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    target_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    received_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    admin_notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mtghub_buylist_card_id (card_id),
    INDEX idx_mtghub_buylist_active (is_active),
    CONSTRAINT fk_mtghub_buylist_card
        FOREIGN KEY (card_id) REFERENCES cards(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mtghub_buylist_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending_submission',
    payout_method ENUM('cash','store_credit') NOT NULL,
    estimated_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    approved_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    store_credit_credited TINYINT(1) NOT NULL DEFAULT 0,
    cash_payout_completed TINYINT(1) NOT NULL DEFAULT 0,
    user_remarks TEXT NULL,
    admin_remarks TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    received_at TIMESTAMP NULL,
    inspected_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    INDEX idx_mtghub_buylist_orders_user_id (user_id),
    INDEX idx_mtghub_buylist_orders_status (status),
    CONSTRAINT fk_mtghub_buylist_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mtghub_buylist_order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    buylist_entry_id INT UNSIGNED NOT NULL,
    card_id INT UNSIGNED NOT NULL,
    declared_condition VARCHAR(50) NULL,
    approved_condition VARCHAR(50) NULL,
    quantity_submitted INT UNSIGNED NOT NULL DEFAULT 0,
    quantity_accepted INT UNSIGNED NOT NULL DEFAULT 0,
    cash_offer_snapshot DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    credit_offer_snapshot DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    estimated_subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    approved_subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    admin_remarks TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mtghub_buylist_order_items_order_id (order_id),
    INDEX idx_mtghub_buylist_order_items_card_id (card_id),
    CONSTRAINT fk_mtghub_buylist_order_items_order
        FOREIGN KEY (order_id) REFERENCES mtghub_buylist_orders(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_mtghub_buylist_order_items_entry
        FOREIGN KEY (buylist_entry_id) REFERENCES mtghub_buylist_entries(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_mtghub_buylist_order_items_card
        FOREIGN KEY (card_id) REFERENCES cards(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
