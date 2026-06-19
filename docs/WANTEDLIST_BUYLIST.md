# Codex Prompt: Integrate Wanted List and MTGHub Platform Buylist Program

You are working on the existing MTGHub PH project located at:

`C:\xampp\htdocs\mtghub`

The system is a XAMPP-compatible PHP 8 / MySQL or MariaDB MVC application using:

* Plain PHP MVC
* PDO prepared statements
* Bootstrap 5 server-rendered views
* Session-based authentication
* Roles: `admin`, `user`
* No Composer
* No Node.js
* No Docker

## Current System Context

The current system already includes:

* Marketplace listings
* Cart and checkout
* Orders
* Existing `/buylist` flow
* Seller-to-buyer offers
* Store credit wallet
* Wallet ledger
* Checkout spending using store credit
* Order refund and seller settlement handling

Important existing tables include:

* `users`
* `cards`
* `listings`
* `cart_items`
* `orders`
* `order_items`
* `wishlist_items`
* `buylist_offers`
* `wallets`
* `wallet_transactions`

The current `/buylist` feature is not a true platform buylist. It is a user wanted-list system where buyers list cards they want, and sellers may send offers from matching active listings.

Do not destroy or rewrite this flow.

---

# Main Objective

Implement two clearly separated programs:

## 1. Wanted List

This is the existing user-to-user buying flow.

Meaning:

```text
User wants to buy cards.
System matches wanted cards with marketplace listings.
Seller may send offer.
Buyer may accept offer.
Accepted offer goes to cart.
Checkout remains unchanged.
```

## 2. MTGHub Buylist

This is a new platform-to-user acquisition flow.

Meaning:

```text
MTGHub posts cards it wants to buy.
Users sell cards directly to MTGHub.
User may choose cash or store credit.
Admin reviews and approves the submission.
Store credit is credited only after admin approval.
```

---

# Phase 1: Rename Existing Buylist to Wanted List

Rename user-facing labels only.

Do not rename database tables yet unless absolutely necessary.

Current existing flow:

```text
/buylist
/buylist/add?card_id=...
wishlist_items
buylist_offers
```

Should now be presented to users as:

```text
Wanted List
My Wanted List
Wanted List Matches
Seller Offers
```

Label replacements:

| Current Label   | New Label           |
| --------------- | ------------------- |
| Buylist         | Wanted List         |
| My Buylist      | My Wanted List      |
| Add to Buylist  | Add to Wanted List  |
| Buylist Matches | Wanted List Matches |
| Buylist Offers  | Seller Offers       |

Preserve route compatibility:

```text
/buylist
/buylist/add
```

Optional aliases may be added:

```text
/wanted-list
/wanted-list/add
```

But do not break old routes.

---

# Phase 2: Add MTGHub Platform Buylist

Create a new module separate from the existing Wanted List.

Use these labels:

User side:

```text
Sell to MTGHub
MTGHub Buylist
My Sell Orders
```

Admin side:

```text
MTGHub Buylist
MTGHub Buylist Orders
```

Suggested routes:

User:

```text
/sell-to-mtghub
/sell-to-mtghub/create?buylist_id=...
/sell-to-mtghub/store
/my-sell-orders
/my-sell-orders/view/{id}
```

Admin:

```text
/admin/mtghub-buylist
/admin/mtghub-buylist/create
/admin/mtghub-buylist/store
/admin/mtghub-buylist/edit/{id}
/admin/mtghub-buylist/update/{id}
/admin/mtghub-buylist/toggle/{id}
/admin/mtghub-buylist/orders
/admin/mtghub-buylist/orders/view/{id}
/admin/mtghub-buylist/orders/receive/{id}
/admin/mtghub-buylist/orders/inspect/{id}
/admin/mtghub-buylist/orders/approve/{id}
/admin/mtghub-buylist/orders/reject/{id}
/admin/mtghub-buylist/orders/complete/{id}
```

---

# Phase 3: Database Changes

Review `database/schema.sql` first.

Add a migration script:

```text
database/add_mtghub_platform_buylist.sql
```

Do not duplicate existing wallet tables. Use existing `wallets` and `wallet_transactions`.

Create these tables if they do not yet exist.

## Table: mtghub_buylist_entries

```sql
CREATE TABLE IF NOT EXISTS mtghub_buylist_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    card_id INT NOT NULL,
    set_name VARCHAR(255) NULL,
    accepted_condition VARCHAR(50) NULL,
    cash_offer DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    credit_offer DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    target_quantity INT NOT NULL DEFAULT 0,
    received_quantity INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    admin_notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mtghub_buylist_card_id (card_id),
    INDEX idx_mtghub_buylist_active (is_active),
    CONSTRAINT fk_mtghub_buylist_card
        FOREIGN KEY (card_id) REFERENCES cards(id)
        ON DELETE CASCADE
);
```

## Table: mtghub_buylist_orders

```sql
CREATE TABLE IF NOT EXISTS mtghub_buylist_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
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
);
```

## Table: mtghub_buylist_order_items

```sql
CREATE TABLE IF NOT EXISTS mtghub_buylist_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    buylist_entry_id INT NOT NULL,
    card_id INT NOT NULL,
    declared_condition VARCHAR(50) NULL,
    approved_condition VARCHAR(50) NULL,
    quantity_submitted INT NOT NULL DEFAULT 0,
    quantity_accepted INT NOT NULL DEFAULT 0,
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
);
```

---

# Phase 4: Models

Create or update models under `app/models`.

Suggested new models:

```text
app/models/MtgHubBuylistEntry.php
app/models/MtgHubBuylistOrder.php
```

The models must support:

## MtgHubBuylistEntry

Methods:

```php
allActive()
allForAdmin()
find($id)
create($data)
update($id, $data)
toggleActive($id)
remainingQuantity($id)
```

## MtgHubBuylistOrder

Methods:

```php
createOrder($userId, $buylistEntryId, $quantity, $declaredCondition, $payoutMethod, $remarks)
findForUser($orderId, $userId)
findForAdmin($orderId)
listForUser($userId)
listForAdmin()
markReceived($orderId)
inspectAndApprove($orderId, $items, $adminRemarks)
reject($orderId, $adminRemarks)
completeCashPayout($orderId, $adminRemarks)
creditStoreCreditIfApproved($orderId)
```

Important:

Use database transactions when:

* Creating sell order
* Approving order
* Crediting store credit
* Completing cash payout

Snapshot the buylist price when the user creates the order.

Use:

```text
cash_offer_snapshot
credit_offer_snapshot
```

This prevents disputes if the admin changes the offer later.

---

# Phase 5: Wallet Integration

Do not create a new wallet system.

Use existing:

```text
wallets
wallet_transactions
```

Use existing wallet transaction style.

For accepted MTGHub Buylist store credit payouts, use:

```text
credit_buylist_settlement
```

or, if more specific transaction types are already enforced, add:

```text
credit_mtghub_buylist_settlement
```

If adding a new type requires schema adjustment, update the migration safely.

Rules:

1. Store credit is credited only after admin approval.
2. Prevent duplicate crediting using `mtghub_buylist_orders.store_credit_credited`.
3. Wallet balance must never become negative.
4. Every credit must create a `wallet_transactions` ledger row.
5. Ledger note should reference the MTGHub Buylist Order ID.

Example ledger note:

```text
MTGHub Buylist settlement for Order #123
```

---

# Phase 6: User Controller and Views

Create controller:

```text
app/controllers/MtgHubBuylistController.php
```

User methods:

```php
index()
create($buylistEntryId)
store()
myOrders()
showMyOrder($id)
```

Views:

```text
app/views/mtghub_buylist/index.php
app/views/mtghub_buylist/create.php
app/views/mtghub_buylist/my_orders.php
app/views/mtghub_buylist/show.php
```

## User Page Requirements

`/sell-to-mtghub` should display:

* Card name
* Set name, if applicable
* Accepted condition
* Cash offer
* Store credit offer
* Target quantity
* Remaining quantity
* Sell button

Sell form should allow:

* Quantity
* Declared condition
* Payout method: cash or store credit
* Remarks

User should see:

* Estimated payout
* Submitted sell orders
* Status
* Approved payout
* Admin remarks

---

# Phase 7: Admin Controller and Views

Create controller:

```text
app/controllers/AdminMtgHubBuylistController.php
```

Admin methods:

```php
index()
create()
store()
edit($id)
update($id)
toggle($id)
orders()
showOrder($id)
markReceived($id)
inspect($id)
approve($id)
reject($id)
completeCash($id)
```

Views:

```text
app/views/admin/mtghub_buylist/index.php
app/views/admin/mtghub_buylist/create.php
app/views/admin/mtghub_buylist/edit.php
app/views/admin/mtghub_buylist/orders.php
app/views/admin/mtghub_buylist/show_order.php
```

Admin must be able to:

* Add buylist entries
* Edit cash offer
* Edit store credit offer
* Set target quantity
* Activate or deactivate entries
* View submitted user sell orders
* Mark order as received
* Inspect submitted cards
* Accept full quantity
* Accept partial quantity
* Reject order
* Complete cash payout
* Trigger store credit payout after approval

---

# Phase 8: Status Rules

Use these order statuses:

```text
pending_submission
pending_receipt
received
under_inspection
accepted
partially_accepted
rejected
completed
cancelled
```

Suggested logic:

* New user submission: `pending_receipt`
* Admin receives cards: `received`
* Admin starts review: `under_inspection`
* Admin approves all submitted qty: `accepted`
* Admin approves partial qty: `partially_accepted`
* Admin rejects all qty: `rejected`
* Store credit credited or cash payout marked paid: `completed`

Store credit path:

```text
pending_receipt
received
under_inspection
accepted / partially_accepted
completed
```

Cash path:

```text
pending_receipt
received
under_inspection
accepted / partially_accepted
completed after admin marks cash payout completed
```

---

# Phase 9: Navigation Updates

Update shared layout navigation.

User menu:

```text
Marketplace
Wanted List
Sell to MTGHub
My Sell Orders
Wallet
```

Admin menu:

```text
Dashboard
Users
Listings
Orders
Prices
Wallets
Wanted Lists
MTGHub Buylist
MTGHub Buylist Orders
```

Do not remove existing admin routes.

---

# Phase 10: Collection Integration

Add optional convenience buttons from user collection.

On `/collection`, for each card:

If card has an active MTGHub Buylist entry, show:

```text
Sell to MTGHub
```

Clicking it opens:

```text
/sell-to-mtghub/create?buylist_id={id}
```

This should not reduce the user’s collection quantity automatically yet unless current collection logic safely supports trade-out movement.

For now, order creation should not mutate collection records.

---

# Phase 11: Search and Matching Logic

Integrate Wanted List and MTGHub Buylist conceptually, not by merging tables.

On user dashboard or card detail page, show two separate signals:

## Wanted List Signal

```text
Users want this card
```

Source:

```text
wishlist_items
```

## MTGHub Buylist Signal

```text
MTGHub is buying this card
```

Source:

```text
mtghub_buylist_entries
```

This lets users understand:

* Other users may want to buy the card.
* MTGHub may also directly buy the card.

Do not merge user wanted-list demand with MTGHub buylist offers.

---

# Phase 12: Security and Validation

Implement:

* `is_logged_in()` protection for user sell order routes
* `is_admin()` protection for admin routes
* Server-side validation for all POST requests
* Positive numeric validation for quantity and amounts
* Cash offer and credit offer cannot be negative
* Submitted quantity cannot exceed remaining target quantity
* User can only view own MTGHub Buylist orders
* Admin can view all MTGHub Buylist orders
* Prevent duplicate wallet crediting
* Use prepared statements
* Use transactions for approval and wallet changes

If CSRF infrastructure already exists, use it.

If not, do not build full CSRF framework unless simple and safe. Add a TODO note because the current workflow document identifies CSRF as a known recommended improvement.

---

# Phase 13: Documentation

Update or create:

```text
docs/MTGHUB_BUYLIST_AND_WANTED_LIST_INTEGRATION.md
```

Include:

* Difference between Wanted List and MTGHub Buylist
* User workflow
* Admin workflow
* Store credit workflow
* Routes added
* Database tables added
* Testing steps

Update:

```text
WORKFLOW_STATUS_OPERATIONS.md
```

Add the new MTGHub Buylist workflow without deleting the existing Buylist and Offer Workflow.

---

# Phase 14: Testing Checklist

After implementation, manually test:

## Wanted List Regression

1. User adds wanted card through `/buylist/add?card_id=...`.
2. `/buylist` displays it as Wanted List.
3. Seller sends offer from matching active listing.
4. Buyer accepts offer.
5. Accepted offer enters cart.
6. Checkout still works.

## MTGHub Buylist

1. Admin creates MTGHub Buylist entry.
2. User opens `/sell-to-mtghub`.
3. User submits sell order using cash payout.
4. Admin receives and approves order.
5. Admin marks cash payout completed.
6. Order status becomes completed.

## Store Credit Payout

1. User submits sell order using store credit payout.
2. Admin receives and approves order.
3. Store credit is credited once.
4. `wallets.balance` increases correctly.
5. `wallet_transactions` records the credit.
6. Repeating approval does not duplicate credit.

## Checkout Store Credit

1. User uses credited store credit in checkout.
2. Wallet debit works.
3. Ledger remains balanced.
4. Negative wallet balance is not allowed.

---

# Required Final Response from Codex

After implementation, report:

1. Files changed
2. New files created
3. SQL migration added
4. Routes added
5. Existing routes preserved
6. Wallet integration details
7. Manual testing steps completed
8. Any deferred items or assumptions
