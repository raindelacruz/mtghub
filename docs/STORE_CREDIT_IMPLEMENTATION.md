# MTGHub PH – Store Credit / Wallet System Implementation

## Project Context

You are working on the existing PHP 8 / MySQL / Apache MVC project located at:

```text
C:\xampp\htdocs\mtghub
```

Project: MTGHub PH

Run URL:

```text
http://localhost/mtghub/public
```

Technology Stack:

- PHP MVC
- MySQL
- PDO
- Bootstrap 5
- Session Authentication

Roles:

- admin
- user

---

# Objective

Implement a **Store Credit / Wallet System** that integrates with the existing marketplace, cart, checkout, orders, buylist, and admin workflows.

This implementation must:

- Extend the current architecture
- Preserve existing functionality
- Follow existing coding conventions
- Remain compatible with XAMPP/PHP/MySQL deployment

---

# Business Concept

Store Credit is a virtual non-withdrawable balance within MTGHub.

Users may earn Store Credit from:

- Trade-ins
- Buylist settlements
- Promotions
- Admin adjustments
- Order refunds

Users may spend Store Credit during checkout.

Store Credit:

✅ Can be spent on purchases

❌ Cannot be withdrawn as cash

---

# Phase 1 Scope

Implement:

- Wallet account per user
- Wallet transaction ledger
- Store credit usage during checkout
- Automatic refunds on order cancellation
- Admin wallet management
- Cart validation improvement

Future trade-in automation is not included in this phase.

---

# Database Changes

## New Table: wallets

```sql
CREATE TABLE wallets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    store_credit_balance DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_wallet_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);
```

---

## New Table: wallet_transactions

```sql
CREATE TABLE wallet_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,

    wallet_id INT NOT NULL,
    user_id INT NOT NULL,

    transaction_type ENUM(
        'credit_admin_adjustment',
        'debit_admin_adjustment',
        'credit_trade_in',
        'credit_order_refund',
        'debit_checkout_payment',
        'credit_promotion',
        'credit_buylist_settlement'
    ) NOT NULL,

    amount DECIMAL(10,2) NOT NULL,

    balance_before DECIMAL(10,2) NOT NULL,
    balance_after DECIMAL(10,2) NOT NULL,

    reference_type VARCHAR(50) NULL,
    reference_id INT NULL,

    notes TEXT NULL,

    created_by INT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_wallet_tx_wallet
        FOREIGN KEY (wallet_id)
        REFERENCES wallets(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_wallet_tx_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_wallet_tx_creator
        FOREIGN KEY (created_by)
        REFERENCES users(id)
        ON DELETE SET NULL
);
```

---

## Orders Table Modifications

Add:

```sql
ALTER TABLE orders
ADD COLUMN payment_method ENUM(
    'cash_gcach_bank',
    'store_credit',
    'mixed'
) DEFAULT 'cash_gcach_bank';

ALTER TABLE orders
ADD COLUMN store_credit_used DECIMAL(10,2)
NOT NULL DEFAULT 0.00;

ALTER TABLE orders
ADD COLUMN cash_amount_due DECIMAL(10,2)
NOT NULL DEFAULT 0.00;

ALTER TABLE orders
ADD COLUMN store_credit_refunded TINYINT(1)
NOT NULL DEFAULT 0;
```

---

# Model Layer

## Wallet Model

Create:

```text
app/Models/Wallet.php
```

### Required Methods

#### getOrCreateByUserId()

```php
Wallet::getOrCreateByUserId($userId)
```

Behavior:

- Create wallet if none exists
- Return wallet record

---

#### getBalance()

```php
Wallet::getBalance($userId)
```

Return current balance.

---

#### credit()

```php
Wallet::credit(
    $userId,
    $amount,
    $transactionType,
    $referenceType = null,
    $referenceId = null,
    $notes = null,
    $createdBy = null
)
```

Requirements:

- Validate amount > 0
- Use DB transaction
- Update balance
- Insert wallet ledger entry

---

#### debit()

```php
Wallet::debit(
    $userId,
    $amount,
    $transactionType,
    $referenceType = null,
    $referenceId = null,
    $notes = null,
    $createdBy = null
)
```

Requirements:

- Validate amount > 0
- Prevent negative balances
- Use DB transaction
- Insert wallet transaction

---

#### transactionsByUser()

```php
Wallet::transactionsByUser($userId)
```

Return transaction history.

---

#### adminAdjust()

```php
Wallet::adminAdjust(
    $userId,
    $amount,
    $direction,
    $notes,
    $adminId
)
```

Direction:

- credit
- debit

Must create ledger entry.

---

# User Wallet Page

## Route

```text
/wallet
```

Access:

- Logged-in users only

---

## Display

### Wallet Summary

```text
Store Credit Balance
₱1,500.00
```

Notice:

```text
Store Credit may be used for MTGHub purchases
but cannot be withdrawn as cash.
```

---

### Transaction History

Columns:

| Date | Type | Amount | Balance After | Reference | Notes |
|--------|--------|--------|--------|--------|--------|

---

## Navigation

Add:

```text
Wallet
Store Credit
```

to authenticated navigation.

---

# Checkout Integration

## Checkout Page

Display:

- Cart Subtotal
- Delivery Fee
- Order Total
- Available Store Credit
- Store Credit To Use
- Remaining Amount Due

---

## Validation Rules

Store Credit:

```text
>= 0
<= wallet balance
<= order total
```

---

## Payment Method Logic

### No Store Credit

```text
payment_method = cash_gcach_bank
```

### Partial Credit

```text
payment_method = mixed
```

### Full Credit

```text
payment_method = store_credit
```

---

# Checkout Processing

Current workflow remains.

Add Store Credit handling.

---

## New Sequence

1. Validate cart
2. Validate inventory
3. Calculate total
4. Validate store credit amount
5. Begin transaction
6. Create order
7. Create order items
8. Reduce listing quantity
9. Debit wallet
10. Create wallet transaction
11. Clear cart
12. Commit transaction

---

## Atomic Requirement

Order creation and wallet deduction must succeed together.

If any step fails:

```text
ROLLBACK EVERYTHING
```

---

# Order Views

Display:

| Field | Value |
|---------|---------|
| Total | ₱2,500 |
| Store Credit Used | ₱1,000 |
| Amount Due | ₱1,500 |
| Payment Method | Mixed |

Applicable to:

- Buyer Orders
- Seller Sales Orders
- Admin Order Views

---

# Cancellation Refund Logic

Current cancellation rules remain.

Add Store Credit refund.

---

## Refund Trigger

If:

```text
order status = cancelled
AND store_credit_used > 0
AND store_credit_refunded = 0
```

Then:

```text
Credit wallet
```

Transaction type:

```text
credit_order_refund
```

Reference:

```text
order id
```

Update:

```text
store_credit_refunded = 1
```

---

# Admin Wallet Management

## Route

```text
/admin/wallets
```

Admin Only.

---

## Features

Search:

- Name
- Email

View:

- Balance
- Transaction History

Actions:

- Credit Store Credit
- Debit Store Credit

---

## Adjustment Form

Fields:

- User
- Direction
- Amount
- Notes

---

## Rules

Debit:

```text
Cannot exceed available balance
```

All changes must create wallet ledger records.

---

# Buylist Future Integration

Do not automate trade-ins yet.

Prepare for future use:

```text
credit_trade_in
credit_buylist_settlement
```

Admin can manually issue Store Credit.

Example:

```text
User submits cards
Admin verifies cards
Admin credits wallet
```

---

# UI Requirements

Use Bootstrap 5.

Currency format:

```text
₱1,500.00
```

Display labels:

- Store Credit Balance
- Store Credit Used
- Cash Amount Due
- Non-withdrawable MTGHub Credit

---

# Security Requirements

Users:

- Must not access other wallets

Admins:

- Only admins can adjust balances

Server-side validation required.

Never trust browser calculations.

Wallet balance must never become negative.

---

# Cart Validation Fix

## Existing Issue

Cart update validates:

```text
quantity >= 1
```

but does not verify:

```text
quantity <= listing quantity
```

before save.

---

## Required Fix

During cart update:

1. Fetch listing
2. Confirm active status
3. Confirm available quantity
4. Reject invalid quantities

Display error message.

---

# Acceptance Tests

## Wallet Creation

- User opens `/wallet`
- Wallet auto-created
- Balance = ₱0.00

---

## Admin Credit

- Admin credits ₱1,000
- Wallet balance becomes ₱1,000
- Ledger entry exists

---

## Partial Credit Checkout

Cart:

```text
₱1,500
```

Store Credit:

```text
₱1,000
```

Expected:

```text
Store Credit Used = ₱1,000
Cash Due = ₱500
Payment Method = mixed
```

---

## Full Credit Checkout

Cart:

```text
₱800
```

Store Credit:

```text
₱1,000
```

Expected:

```text
Store Credit Used = ₱800
Cash Due = ₱0
Payment Method = store_credit
Remaining Balance = ₱200
```

---

## Insufficient Credit

Attempt:

```text
Use ₱2,000
Balance = ₱1,000
```

Expected:

- Checkout blocked
- No order created
- No inventory changes
- No wallet transaction

---

## Cancellation Refund

Cancel order.

Expected:

- Listing quantity restored
- Wallet refunded once
- No duplicate refunds

---

## Cart Quantity Validation

Attempt quantity greater than listing stock.

Expected:

- Validation error
- Quantity not saved

---

# Development Rules

Before implementation:

1. Review routes
2. Review controllers
3. Review models
4. Review views
5. Review schema.sql

Follow existing coding style.

Do not:

- Convert to Laravel
- Replace MVC structure
- Rewrite marketplace workflow

Implement incrementally and safely.