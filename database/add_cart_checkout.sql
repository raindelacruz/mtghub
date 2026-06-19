USE mtghub;

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

SET @schema_name = DATABASE();

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE orders ADD logistics_method ENUM(''meetup'', ''lbc'') NOT NULL DEFAULT ''meetup'' AFTER delivery_preference',
        'SELECT ''orders.logistics_method already exists'' AS message'
    )
    FROM information_schema.columns
    WHERE table_schema = @schema_name
      AND table_name = 'orders'
      AND column_name = 'logistics_method'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE orders ADD logistics_fee_php DECIMAL(10, 2) NOT NULL DEFAULT 0.00 AFTER logistics_method',
        'SELECT ''orders.logistics_fee_php already exists'' AS message'
    )
    FROM information_schema.columns
    WHERE table_schema = @schema_name
      AND table_name = 'orders'
      AND column_name = 'logistics_fee_php'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE orders ADD payment_method VARCHAR(80) NOT NULL DEFAULT ''Manual payment'' AFTER logistics_fee_php',
        'SELECT ''orders.payment_method already exists'' AS message'
    )
    FROM information_schema.columns
    WHERE table_schema = @schema_name
      AND table_name = 'orders'
      AND column_name = 'payment_method'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;

SET @sql = (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE orders ADD payment_reference VARCHAR(150) NOT NULL DEFAULT '''' AFTER payment_method',
        'SELECT ''orders.payment_reference already exists'' AS message'
    )
    FROM information_schema.columns
    WHERE table_schema = @schema_name
      AND table_name = 'orders'
      AND column_name = 'payment_reference'
);
PREPARE statement FROM @sql;
EXECUTE statement;
DEALLOCATE PREPARE statement;
