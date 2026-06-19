CREATE DATABASE IF NOT EXISTS mtghub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE mtghub;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    first_name VARCHAR(80) NOT NULL DEFAULT '',
    middle_initial VARCHAR(5) NOT NULL DEFAULT '',
    last_name VARCHAR(80) NOT NULL DEFAULT '',
    email VARCHAR(150) NOT NULL UNIQUE,
    email_verified_at DATETIME NULL,
    contact_number VARCHAR(30) NOT NULL DEFAULT '',
    password_hash VARCHAR(255) NOT NULL,
    address_number VARCHAR(50) NOT NULL DEFAULT '',
    address_street VARCHAR(150) NOT NULL DEFAULT '',
    address_barangay VARCHAR(100) NOT NULL DEFAULT '',
    address_province VARCHAR(100) NOT NULL DEFAULT '',
    address_city VARCHAR(100) NOT NULL DEFAULT '',
    address_postal_code VARCHAR(20) NOT NULL DEFAULT '',
    shipping_same_as_complete TINYINT(1) NOT NULL DEFAULT 0,
    shipping_number VARCHAR(50) NOT NULL DEFAULT '',
    shipping_street VARCHAR(150) NOT NULL DEFAULT '',
    shipping_barangay VARCHAR(100) NOT NULL DEFAULT '',
    shipping_province VARCHAR(100) NOT NULL DEFAULT '',
    shipping_city VARCHAR(100) NOT NULL DEFAULT '',
    shipping_postal_code VARCHAR(20) NOT NULL DEFAULT '',
    delivery_mode VARCHAR(40) NOT NULL DEFAULT 'meetup',
    payment_mode VARCHAR(40) NOT NULL DEFAULT 'gcash',
    city VARCHAR(100) NOT NULL,
    province VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    account_status ENUM('pending','active','suspended','banned') NOT NULL DEFAULT 'pending',
    seller_bio VARCHAR(500) NULL,
    suspension_reason VARCHAR(500) NULL,
    moderation_notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_account_status (account_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_verification_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_verification_user (user_id),
    INDEX idx_email_verification_expiry (expires_at),
    CONSTRAINT fk_email_verification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT UNSIGNED NOT NULL,
    subject_type ENUM('user','listing','review') NOT NULL,
    subject_id INT UNSIGNED NOT NULL,
    reason ENUM('suspected_scam','counterfeit','harassment','inappropriate','other') NOT NULL,
    details TEXT NOT NULL,
    status ENUM('open','reviewing','resolved','dismissed') NOT NULL DEFAULT 'open',
    resolution_notes TEXT NULL,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_reports_status_created (status, created_at),
    INDEX idx_reports_subject (subject_type, subject_id),
    CONSTRAINT fk_reports_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_reports_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id BIGINT UNSIGNED NULL,
    metadata_json LONGTEXT NULL,
    ip_hash CHAR(64) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_admin_created (admin_id, created_at),
    INDEX idx_audit_target (target_type, target_id),
    CONSTRAINT fk_audit_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email_hash CHAR(64) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_login_attempt_pair_time (email_hash, ip_hash, attempted_at),
    INDEX idx_login_attempt_ip_time (ip_hash, attempted_at),
    INDEX idx_login_attempt_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_reset_user (user_id),
    INDEX idx_password_reset_expiry (expires_at),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (username, email, email_verified_at, password_hash, city, province, role, account_status)
VALUES (
    'admin',
    'admin@mtghub.local',
    NOW(),
    '$2y$10$T84kAkEUDI7k/xXCMMFoueXQJ5VgZAmdI3wwCic57nIonFMkJtEKC',
    'Quezon City',
    'Metro Manila',
    'admin',
    'active'
)
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO users (username, email, email_verified_at, password_hash, city, province, role, account_status)
VALUES (
    'sampleuser',
    'user@mtghub.local',
    NOW(),
    '$2y$10$sDM/1fxUmlBZirmANtQgoeuuvWYd2Yiu9YgSwuaoMp2f1rxaeMK1q',
    'Makati City',
    'Metro Manila',
    'user',
    'active'
)
ON DUPLICATE KEY UPDATE email = email;

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
        'debit_order_refund_recovery',
        'debit_checkout_payment',
        'credit_promotion',
        'credit_buylist_settlement'
    ) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    balance_before DECIMAL(10, 2) NOT NULL,
    balance_after DECIMAL(10, 2) NOT NULL,
    reference_type VARCHAR(50) NULL,
    reference_id INT UNSIGNED NULL,
    idempotency_key VARCHAR(120) NULL UNIQUE,
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

CREATE TABLE IF NOT EXISTS cards (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_name VARCHAR(150) NOT NULL,
    set_name VARCHAR(150) NOT NULL,
    collector_number VARCHAR(30) NULL,
    rarity ENUM('common', 'uncommon', 'rare', 'mythic') NOT NULL,
    color VARCHAR(50) NULL,
    type_line VARCHAR(150) NOT NULL,
    image_url VARCHAR(500) NULL,
    scryfall_id VARCHAR(80) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cards_name (card_name),
    INDEX idx_cards_set (set_name),
    INDEX idx_cards_rarity (rarity),
    INDEX idx_cards_color (color),
    UNIQUE KEY uq_cards_identity (card_name, set_name, collector_number),
    UNIQUE KEY uq_cards_scryfall_id (scryfall_id),
    FULLTEXT KEY ft_cards_search (card_name, set_name, type_line)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO cards (card_name, set_name, collector_number, rarity, color, type_line, image_url, scryfall_id)
VALUES
    ('Sol Ring', 'Commander Masters', '399', 'uncommon', 'Colorless', 'Artifact', NULL, NULL),
    ('Lightning Bolt', 'Double Masters', '129', 'common', 'Red', 'Instant', NULL, NULL),
    ('Swords to Plowshares', 'Dominaria Remastered', '31', 'uncommon', 'White', 'Instant', NULL, NULL)
ON DUPLICATE KEY UPDATE card_name = card_name;

CREATE TABLE IF NOT EXISTS collections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    card_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    card_condition ENUM('near_mint', 'lightly_played', 'moderately_played', 'heavily_played', 'damaged') NOT NULL DEFAULT 'near_mint',
    language VARCHAR(50) NOT NULL DEFAULT 'English',
    is_foil TINYINT(1) NOT NULL DEFAULT 0,
    acquisition_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_collection_user (user_id),
    INDEX idx_collection_card (card_id),
    CONSTRAINT fk_collection_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_collection_card FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS price_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    card_id INT UNSIGNED NOT NULL,
    source_name VARCHAR(150) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'PHP',
    price DECIMAL(10, 2) NOT NULL,
    converted_php_price DECIMAL(10, 2) NOT NULL,
    date_captured DATE NOT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_price_card (card_id),
    INDEX idx_price_date (date_captured),
    CONSTRAINT fk_price_card FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS listings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    card_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    card_condition ENUM('near_mint', 'lightly_played', 'moderately_played', 'heavily_played', 'damaged') NOT NULL DEFAULT 'near_mint',
    price_php DECIMAL(10, 2) NOT NULL,
    seller_location VARCHAR(150) NOT NULL,
    delivery_options VARCHAR(255) NOT NULL,
    status ENUM('active', 'reserved', 'sold', 'cancelled') NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_listing_user (user_id),
    INDEX idx_listing_card (card_id),
    INDEX idx_listing_status (status),
    INDEX idx_listing_price (price_php),
    CONSTRAINT fk_listing_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_listing_card FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    listing_id INT UNSIGNED NOT NULL,
    buyer_id INT UNSIGNED NOT NULL,
    seller_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price_php DECIMAL(10, 2) NOT NULL,
    total_price_php DECIMAL(10, 2) NOT NULL,
    buyer_location VARCHAR(150) NOT NULL,
    delivery_preference VARCHAR(255) NOT NULL,
    logistics_method ENUM('meetup', 'lbc') NOT NULL DEFAULT 'meetup',
    logistics_fee_php DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash_gcach_bank', 'store_credit', 'mixed') NOT NULL DEFAULT 'cash_gcach_bank',
    external_payment_method VARCHAR(80) NOT NULL DEFAULT '',
    store_credit_used DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    cash_amount_due DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    store_credit_refunded TINYINT(1) NOT NULL DEFAULT 0,
    store_credit_refunded_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    store_credit_settled TINYINT(1) NOT NULL DEFAULT 0,
    payment_reference VARCHAR(150) NOT NULL DEFAULT '',
    payment_deadline DATETIME NULL,
    payment_status ENUM('pending','submitted','verified','rejected','refunded') NOT NULL DEFAULT 'pending',
    fulfillment_status ENUM('awaiting_payment','awaiting_fulfillment','preparing','shipped','ready_for_meetup','delivered','completed','cancelled') NOT NULL DEFAULT 'awaiting_payment',
    settlement_available_at DATETIME NULL,
    settled_at DATETIME NULL,
    tracking_carrier VARCHAR(80) NULL,
    tracking_reference VARCHAR(150) NULL,
    shipped_at DATETIME NULL,
    delivered_at DATETIME NULL,
    completed_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    status ENUM('pending_payment','payment_submitted','payment_verified','preparing','shipped','ready_for_meetup','delivered','buyer_confirmed','completed','cancelled','expired','disputed','refunded') NOT NULL DEFAULT 'pending_payment',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_listing (listing_id),
    INDEX idx_order_buyer (buyer_id),
    INDEX idx_order_seller (seller_id),
    INDEX idx_order_status (status),
    CONSTRAINT fk_order_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    listing_id INT UNSIGNED NOT NULL,
    card_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price_php DECIMAL(10, 2) NOT NULL,
    line_total_php DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_item_order (order_id),
    INDEX idx_order_item_listing (listing_id),
    INDEX idx_order_item_card (card_id),
    CONSTRAINT fk_order_item_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_item_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_item_card FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS order_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    buyer_read_at DATETIME NULL,
    seller_read_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_messages_order_created (order_id, created_at),
    CONSTRAINT fk_order_message_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_message_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    body VARCHAR(500) NOT NULL,
    action_url VARCHAR(255) NULL,
    read_at DATETIME NULL,
    emailed_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user_read_created (user_id, read_at, created_at),
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_preferences (
    user_id INT UNSIGNED PRIMARY KEY,
    email_order_updates TINYINT(1) NOT NULL DEFAULT 1,
    email_messages TINYINT(1) NOT NULL DEFAULT 0,
    email_offers TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notification_preferences_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT UNSIGNED NOT NULL,
    listing_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cart_buyer (buyer_id),
    INDEX idx_cart_listing (listing_id),
    UNIQUE KEY uq_cart_buyer_listing (buyer_id, listing_id),
    CONSTRAINT fk_cart_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wishlist_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    card_id INT UNSIGNED NOT NULL,
    desired_quantity INT UNSIGNED NOT NULL DEFAULT 1,
    max_price_php DECIMAL(10, 2) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_wishlist_user (user_id),
    INDEX idx_wishlist_card (card_id),
    UNIQUE KEY uq_wishlist_user_card (user_id, card_id),
    CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_wishlist_card FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS buylist_offers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT UNSIGNED NOT NULL,
    seller_id INT UNSIGNED NOT NULL,
    listing_id INT UNSIGNED NOT NULL,
    wishlist_item_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    message VARCHAR(255) NULL,
    status ENUM('pending', 'accepted', 'declined', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_buylist_offer_buyer (buyer_id),
    INDEX idx_buylist_offer_seller (seller_id),
    INDEX idx_buylist_offer_listing (listing_id),
    INDEX idx_buylist_offer_wishlist_item (wishlist_item_id),
    INDEX idx_buylist_offer_status (status),
    CONSTRAINT fk_buylist_offer_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_buylist_offer_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_buylist_offer_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    CONSTRAINT fk_buylist_offer_wishlist_item FOREIGN KEY (wishlist_item_id) REFERENCES wishlist_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    CONSTRAINT fk_mtghub_buylist_card FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
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
    CONSTRAINT fk_mtghub_buylist_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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
    CONSTRAINT fk_mtghub_buylist_order_items_order FOREIGN KEY (order_id) REFERENCES mtghub_buylist_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_mtghub_buylist_order_items_entry FOREIGN KEY (buylist_entry_id) REFERENCES mtghub_buylist_entries(id) ON DELETE RESTRICT,
    CONSTRAINT fk_mtghub_buylist_order_items_card FOREIGN KEY (card_id) REFERENCES cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
