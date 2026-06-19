USE mtghub;

ALTER TABLE wallet_transactions
    MODIFY transaction_type ENUM(
        'credit_admin_adjustment',
        'debit_admin_adjustment',
        'credit_trade_in',
        'credit_order_refund',
        'credit_order_settlement',
        'debit_checkout_payment',
        'credit_promotion',
        'credit_buylist_settlement'
    ) NOT NULL;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS store_credit_settled TINYINT(1) NOT NULL DEFAULT 0;

INSERT INTO wallets (user_id, store_credit_balance)
SELECT DISTINCT orders.seller_id, 0.00
FROM orders
LEFT JOIN wallets ON wallets.user_id = orders.seller_id
WHERE orders.status = 'completed'
  AND orders.store_credit_used > 0
  AND orders.store_credit_settled = 0
  AND wallets.id IS NULL;

INSERT INTO wallet_transactions (
    wallet_id,
    user_id,
    transaction_type,
    amount,
    balance_before,
    balance_after,
    reference_type,
    reference_id,
    notes,
    created_by
)
SELECT
    wallets.id,
    orders.seller_id,
    'credit_order_settlement',
    orders.store_credit_used,
    wallets.store_credit_balance,
    wallets.store_credit_balance + orders.store_credit_used,
    'order',
    orders.id,
    CONCAT('Store credit settlement for completed order #', orders.id),
    NULL
FROM orders
INNER JOIN wallets ON wallets.user_id = orders.seller_id
WHERE orders.status = 'completed'
  AND orders.store_credit_used > 0
  AND orders.store_credit_settled = 0;

UPDATE wallets
INNER JOIN (
    SELECT seller_id, SUM(store_credit_used) AS settlement_total
    FROM orders
    WHERE status = 'completed'
      AND store_credit_used > 0
      AND store_credit_settled = 0
    GROUP BY seller_id
) settlements ON settlements.seller_id = wallets.user_id
SET wallets.store_credit_balance = wallets.store_credit_balance + settlements.settlement_total;

UPDATE orders
SET store_credit_settled = 1
WHERE status = 'completed'
  AND store_credit_used > 0
  AND store_credit_settled = 0;
