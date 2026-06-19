# MTGHub PH Workflow, Status, and Operations

Last updated: 2026-06-16

## Purpose

MTGHub PH is a XAMPP-compatible PHP 8 and MySQL/MariaDB MVP for Philippine Magic: The Gathering collection tracking, price tracking, marketplace listings, wanted-list matching, MTGHub platform buylist submissions, cart checkout, order management, and store credit wallets.

The app is intentionally simple:

- No Composer dependency stack
- No Node.js build step
- No Docker requirement
- Plain PHP MVC structure
- PDO prepared statements for database access
- Bootstrap 5 views rendered server-side

## Current Status

The project currently includes the requested MVP phases plus later marketplace and wallet improvements.

Implemented:

- User registration, login, logout, session authentication, roles, and profile details
- Card database with manual admin entry, search/filtering, and Scryfall import/sync support
- Personal collection tracker with quantity, condition, language, foil, acquisition price, and notes
- Manual price tracker with Philippine peso conversion support
- Marketplace listings with seller location, delivery options, quantity, price, and status
- Cart with one-seller-at-a-time checkout validation
- Orders with buyer order history and seller sales order management
- Wanted List flow with listing matches and seller-to-buyer offers
- MTGHub platform buylist flow with user sell orders, admin review, cash payout completion, and store credit settlement
- Admin dashboard for users, listings, orders, prices, and wallets
- Store credit wallet balance, ledger, checkout spending, cancellation refunds, and seller settlement on completed orders
- Local session storage in `storage/sessions`
- Optional Scryfall bulk file storage in `storage/scryfall/default_cards.json`

Known recommended improvements:

- Add CSRF tokens to all POST forms
- Add pagination for large tables
- Add password change/reset
- Add payment proof upload and seller-buyer messaging
- Add image upload support
- Add automated browser QA against XAMPP Apache
- Improve automated tests; the current project is primarily manually tested through the browser

## Project Layout

```text
app/
  controllers/   Request handlers for auth, cards, collection, listings, cart, orders, wanted list, MTGHub buylist, wallet, admin
  core/          Router, controller base class, PDO database wrapper, helper functions
  models/        Database access and business logic
  services/      Scryfall API and bulk import helpers
  views/         PHP templates rendered inside the shared layout
assets/
  css/           Application styling
config/
  app.php        Base URL, asset URL, app name
  database.php   PDO connection settings
database/
  schema.sql     Full database schema and seed data
  add_*.sql      One-time migration scripts for existing installs
docs/
  *.md           Implementation and operations notes
public/
  index.php      Front controller and route table
storage/
  sessions/      PHP session files
  scryfall/      Downloaded Scryfall bulk JSON
```

## Runtime Configuration

Default local URL:

```text
http://localhost/mtghub/public
```

Database settings live in:

```text
config/database.php
```

Default XAMPP values:

```php
'host' => '127.0.0.1',
'database' => 'mtghub',
'username' => 'root',
'password' => '',
```

Application URL settings live in:

```text
config/app.php
```

If the folder name changes, update:

```php
'base_url' => '/mtghub/public',
'asset_url' => '/mtghub/assets',
```

## How Requests Work

1. Browser requests enter through `public/index.php`.
2. `public/index.php` loads `app/init.php`.
3. `app/init.php` starts sessions, defines paths/constants, loads helpers, database, controller base, and router.
4. `public/index.php` registers GET and POST routes.
5. `Router::dispatch()` reads `$_GET['url']` and `$_SERVER['REQUEST_METHOD']`.
6. The matching controller file is required from `app/controllers`.
7. The controller method validates access and request data.
8. Models in `app/models` use `Database::connection()` for PDO queries.
9. The controller renders a view through `Controller::view()`.
10. The selected view is wrapped by `app/views/layouts/main.php`.

If Apache rewrite is unavailable, routes can be reached with query-string routing:

```text
http://localhost/mtghub/public/index.php?url=login
```

## Main User Workflows

### Account Workflow

1. Visitor opens `/register`.
2. `AuthController` validates registration data.
3. Passwords are hashed before storage.
4. User logs in through `/login`.
5. Logged-in user details are stored in the PHP session.
6. Role checks use `is_logged_in()` and `is_admin()` helper functions.
7. User can update profile and location through `/profile`.

Default seeded accounts:

```text
Admin: admin@mtghub.local / Admin123!
User:  user@mtghub.local / User123!
```

### Card Database Workflow

1. Users browse `/cards` and filter/search cards.
2. Admins can manually create or edit cards.
3. Admins can import cards from Scryfall through `/cards/import`.
4. Admins can run bulk Scryfall sync through `/cards/sync-scryfall`.
5. Bulk sync downloads the Scryfall default cards file into `storage/scryfall/default_cards.json`.
6. Cards are upserted into the `cards` table using Scryfall IDs where available.

### Collection Workflow

1. Logged-in user opens a card and chooses to add it to collection.
2. Collection item stores quantity, condition, language, foil flag, acquisition price, and notes.
3. `/collection` displays the signed-in user's collection.
4. Collection value is estimated from locally stored card and price data.

### Price Tracker Workflow

1. Admin opens `/prices/create?card_id=...`.
2. Admin records source name, currency, raw price, PHP converted price, capture date, and notes.
3. `/prices` shows recent price entries.
4. Admin can manage/delete price entries from `/admin/prices`.

### Listing Workflow

1. Logged-in user creates a listing for a card from `/listings/create?card_id=...`.
2. Listing stores quantity, condition, PHP price, seller location, delivery options, status, and notes.
3. `/listings` shows marketplace inventory.
4. `/listings/mine` shows the seller's own listings.
5. Admins can review and change listing status from `/admin/listings`.

Listing statuses:

```text
active, reserved, sold, cancelled
```

### Cart and Checkout Workflow

1. Buyer adds an active listing to `/cart`.
2. Cart prevents users from buying their own listings.
3. Cart enforces one seller per checkout.
4. Cart update validates that requested quantity does not exceed active listing stock.
5. Buyer opens `/orders/checkout`.
6. Checkout validates buyer location, logistics method, delivery details, payment data, cart stock, and store credit amount.
7. `Order::createFromCart()` starts a database transaction.
8. Cart rows and listing quantities are locked with `FOR UPDATE`.
9. Order and order items are created.
10. Listing quantities are reduced and may move to `reserved`.
11. Store credit is debited if used.
12. Cart is cleared.
13. Transaction commits; otherwise all changes are rolled back.

Checkout supports:

```text
meetup: no logistics fee
lbc:    PHP 100.00 logistics fee
```

Payment method is derived automatically:

```text
cash_gcach_bank  No store credit used
mixed            Partial store credit plus external payment
store_credit     Full payment through store credit
```

### Order Status Workflow

Buyer:

- Can view orders at `/orders`
- Can cancel pending orders

Seller:

- Can view sales at `/orders/sales`
- Can move pending orders to confirmed or cancelled
- Can move confirmed orders to completed or cancelled

When an order is cancelled:

- Ordered listing quantities are restored unless the listing is cancelled
- Store credit used by the buyer is refunded once
- `orders.store_credit_refunded` prevents duplicate refunds

When an order is completed:

- Empty listings can move to `sold`
- Store credit used by the buyer is credited to the seller once
- `orders.store_credit_settled` prevents duplicate settlement

### Wanted List and Offer Workflow

1. Buyer adds wanted cards through `/buylist/add?card_id=...` or `/wanted-list/add?card_id=...`.
2. Wanted list item stores desired quantity, optional max price, and notes.
3. `/buylist` and `/wanted-list` show wanted cards, matching active listings, and received offers.
4. Seller can send an offer from a matching active listing.
5. Offer quantity is capped by listing quantity and desired quantity.
6. Buyer can accept or decline pending offers.
7. Accepting an offer adds the listing to the buyer's cart and marks the offer accepted.
8. Checkout remains the same cart/order workflow.

### MTGHub Buylist Workflow

This is separate from the Wanted List. MTGHub is the buyer and users sell cards directly to the platform.

User:

1. User opens `/sell-to-mtghub`.
2. User chooses an active MTGHub Buylist entry.
3. User submits quantity, declared condition, payout method, and remarks.
4. The sell order is created as `pending_receipt`.
5. User tracks sell orders at `/my-sell-orders`.

Admin:

1. Admin manages entries at `/admin/mtghub-buylist`.
2. Admin reviews submissions at `/admin/mtghub-buylist/orders`.
3. Admin marks a submitted order as `received`.
4. Admin moves the order to `under_inspection`.
5. Admin approves full or partial quantities, or rejects the order.
6. Accepted cash orders become `completed` after admin marks cash payout completed.
7. Accepted store credit orders credit the user's wallet and become `completed`.

MTGHub Buylist order statuses:

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

Store credit settlement:

- Uses existing `wallets` and `wallet_transactions`.
- Uses transaction type `credit_buylist_settlement`.
- Uses reference type `mtghub_buylist_order`.
- `mtghub_buylist_orders.store_credit_credited` prevents duplicate credits.
- Ledger note references the MTGHub Buylist order ID.

### Wallet and Store Credit Workflow

1. Logged-in user opens `/wallet`.
2. Wallet is automatically created if missing.
3. Wallet page shows current balance and ledger history.
4. Admin manages balances from `/admin/wallets`.
5. Admin wallet adjustments require user, direction, amount, and notes.
6. Every credit/debit writes a `wallet_transactions` ledger row.
7. Wallet changes are transaction-safe and prevent negative balances.

Wallet transaction types currently supported:

```text
credit_admin_adjustment
debit_admin_adjustment
credit_trade_in
credit_order_refund
credit_order_settlement
debit_checkout_payment
credit_promotion
credit_buylist_settlement
```

## Admin Operations

Admin-only routes:

```text
/admin
/admin/users
/admin/listings
/admin/orders
/admin/prices
/admin/wallets
/admin/wanted-lists
/admin/mtghub-buylist
/admin/mtghub-buylist/orders
/cards/create
/cards/import
/cards/edit
/prices/create
```

Common admin tasks:

- Review dashboard counts and recent marketplace activity
- Promote/demote users, except removing own admin role is blocked
- Add or edit cards
- Import Scryfall card data
- Add or delete price records
- Hide, cancel, reserve, reactivate, or mark listings sold
- Review orders and payment/store-credit breakdowns
- Credit or debit user store credit with ledger notes
- Review user Wanted List demand
- Manage MTGHub Buylist entries and sell orders

## Database Tables

Core tables:

- `users`
- `cards`
- `collections`
- `price_history`
- `listings`
- `cart_items`
- `orders`
- `order_items`
- `wishlist_items`
- `buylist_offers`
- `mtghub_buylist_entries`
- `mtghub_buylist_orders`
- `mtghub_buylist_order_items`
- `wallets`
- `wallet_transactions`

Important relationships:

- Users own collections, listings, cart rows, buylist items, wallets, and orders.
- Cards connect to collections, prices, listings, order items, and buylist items.
- Orders belong to one buyer and one seller.
- Order items preserve the actual purchased listing/card lines.
- Wallet transactions preserve every store credit movement.

## Operational Runbook

### Fresh Local Install

1. Place the project at:

   ```text
   C:\xampp\htdocs\mtghub
   ```

2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin:

   ```text
   http://localhost/phpmyadmin
   ```

4. Import:

   ```text
   database/schema.sql
   ```

5. Visit:

   ```text
   http://localhost/mtghub/public
   ```

### Existing Install Upgrade

For older databases, import the one-time migration scripts as needed:

```text
database/add_registration_details.sql
database/add_orders.sql
database/add_cart_checkout.sql
database/add_buylist_offers.sql
database/add_store_credit_wallet.sql
database/add_store_credit_seller_settlement.sql
database/add_mtghub_platform_buylist.sql
database/optimize_cards_search.sql
```

Import only scripts that have not already been applied.

### Scryfall Sync

1. Log in as admin.
2. Open `/cards/import`.
3. Use a small test limit first, such as `100`.
4. If successful, run full sync.
5. For large databases, import `database/optimize_cards_search.sql` if the full-text index is not already present.

Notes:

- Scryfall images are stored as URLs.
- Image files are not downloaded locally.
- Bulk JSON is stored in `storage/scryfall/default_cards.json`.

### Manual Smoke Test

After changes, verify:

1. Register and log in as a normal user.
2. Log in as admin and create/import a card.
3. Add a card to collection.
4. Add a price entry as admin.
5. Create a listing as one user.
6. Add listing to cart as another user.
7. Checkout with no store credit.
8. Admin credits store credit to buyer.
9. Checkout with partial or full store credit.
10. Cancel an order and confirm store credit refund.
11. Complete an order and confirm seller store credit settlement.
12. Add a card to buylist, send seller offer, accept offer, and confirm it enters cart.
13. Create an MTGHub Buylist entry as admin.
14. Submit a cash sell order as a user, approve it as admin, and mark cash payout completed.
15. Submit a store credit sell order as a user, approve it as admin, and confirm the wallet balance and ledger update once.

## Security and Data Integrity Notes

Current protections:

- Passwords use hashing.
- Database access uses PDO prepared statements.
- User access checks prevent anonymous protected actions.
- Admin-only screens check role before access.
- Users cannot access wallets or order/listing actions outside their allowed scope.
- Checkout uses database transactions.
- Cart/listing quantities are validated before checkout.
- Wallet debits cannot make balances negative.
- Refunds and settlements are guarded against duplicate execution.

Important gap:

- POST forms should receive CSRF tokens before production use.

## Operational Definition of Done

A change is operationally ready when:

- The related route loads without PHP errors.
- Invalid input returns a useful flash/view error.
- Valid input writes the expected database rows.
- User ownership and admin role rules are enforced.
- Marketplace quantities remain correct after checkout, cancellation, and completion.
- Wallet balance matches the wallet ledger after credit, debit, refund, and settlement flows.
- README or docs are updated when routes, schema, setup, or workflows change.
