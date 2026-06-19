# MTGHub PH

MTGHub PH is a Philippine-focused Magic: The Gathering MVP for collection tracking, price tracking, buylists, and marketplace listings.

This repository currently implements Phases 1-8 plus cart checkout and buylist offers: PHP MVC bootstrap, PDO database connection, Bootstrap 5 layout, Magic Archive theme, session authentication, user registration, login, logout, password hashing, roles, card database, personal collection tracking, manual Philippine price tracking, marketplace listings, buyer carts, checkout with payment/logistics details, seller order management, buylist matching, seller-to-buyer offers, the admin panel, and UI/dashboard polish.

## Requirements

- XAMPP with Apache and MySQL/MariaDB
- PHP 8.x
- Browser access to phpMyAdmin
- No Node.js, Composer, Docker, PostgreSQL, Prisma, Next.js, or TypeScript required

## Install in XAMPP

1. Place this folder at:

   ```text
   C:\xampp\htdocs\mtghub
   ```

2. Start Apache and MySQL from the XAMPP Control Panel.

3. Open phpMyAdmin:

   ```text
   http://localhost/phpmyadmin
   ```

4. Import the database:

   - Click the `Import` tab.
   - Choose `database/schema.sql`.
   - Click `Go`.

   For an existing MTGHub database that already has the earlier schema, import `database/add_orders.sql` once to add the orders table, import `database/add_cart_checkout.sql` once to add cart checkout support, import `database/add_buylist_offers.sql` once to add seller offers for buylists, then import `database/add_store_credit_wallet.sql` once to add store credit wallets.

   Import `database/add_phase1_security.sql` once on existing databases to add login throttling and password-reset token storage.

   Import `database/add_phase2_trust_moderation.sql` once to add verification, account moderation, marketplace reports, and admin audit records. Existing users are activated and email-verified by the migration; new registrations require verification.

   Import `database/add_phase3_order_lifecycle.sql` once to add payment deadlines, tracking, fulfillment states, and order history.

   Import `database/add_phase4_payment_safety.sql` once to add private payment proofs, separate payment/fulfillment states, delayed settlement, and idempotent wallet transactions.

   Import `database/add_phase5_messaging_notifications.sql` once to add order messaging, in-app notifications, read state, and email preferences.

   Import `database/add_phase6_disputes_reviews.sql` once to add disputes, refund accounting, verified-purchase reviews, seller metrics, and review moderation.

   Phase 7 and later use versioned migrations. Run `C:\xampp\php\php.exe scripts\migrate.php`; applied checksums are recorded in `schema_migrations` and changed historical migrations are rejected.

5. Confirm the database name is `mtghub`.

6. Visit the app:

   ```text
   http://localhost/mtghub/public
   ```

7. Before public use, follow [the production runbook](docs/PRODUCTION_RUNBOOK.md) and run the complete gate in [the test guide](tests/README.md).

## Default Seeded Accounts

- Admin
  - Email: `admin@mtghub.local`
  - Password: `Admin123!`
- User
  - Email: `user@mtghub.local`
  - Password: `User123!`

Use the profile page to update the admin username and location after first login.

## Configuration

Database settings are in:

```text
config/database.php
```

The default XAMPP values are:

```php
'host' => '127.0.0.1',
'database' => 'mtghub',
'username' => 'root',
'password' => '',
```

Application URL settings are in:

```text
config/app.php
```

For deployment, copy `.env.example` to `.env`, set `MTGHUB_ENV=production`, and replace the database credentials. System environment variables with the same names take precedence over `.env` values. The `.env` file is ignored by version control.

Email verification and password resets use authenticated SMTP. Configure the `MTGHUB_SMTP_*` and `MTGHUB_MAIL_FROM_*` values in `.env`. Gmail accounts must use a Google app password rather than the normal account password.

For reliable payment-deadline expiry, schedule this command every five minutes with Windows Task Scheduler:

```text
C:\xampp\php\php.exe C:\xampp\htdocs\mtghub\scripts\expire_orders.php
```

Order pages also run the same expiry check when buyers or sellers visit them.

If the project folder name changes, update:

```php
'base_url' => '/mtghub/public',
'asset_url' => '/mtghub/assets',
```

## Current Routes

- `/` - Home
- `/register` - Register
- `/login` - Login
- `/logout` - Logout (POST action)
- `/forgot-password` - Request a password reset
- `/reset-password` - Use a time-limited password reset link
- `/change-password` - Change the signed-in user's password
- `/verify-email` - Email verification status and resend action
- `/sellers/show?id=1` - Public seller profile and verification indicators
- `/reports/create?type=user&id=1` - Report a seller or marketplace listing
- `/cards` - Card database and filters
- `/cards/show?id=1` - Card detail
- `/cards/create` - Add card, admin only
- `/cards/import` - Search and import card data from Scryfall, admin only
- `/cards/sync-scryfall` - Bulk sync Scryfall default card data, admin only
- `/cards/edit?id=1` - Edit card, admin only
- `/collection` - Signed-in user's collection
- `/collection/add?card_id=1` - Add a card to collection
- `/collection/edit?id=1` - Edit collection item
- `/prices` - Recent price entries
- `/prices/create?card_id=1` - Add price entry, admin only
- `/listings` - Marketplace listings
- `/listings/mine` - Signed-in user's listings
- `/listings/create?card_id=1` - Create listing
- `/listings/edit?id=1` - Edit listing
- `/cart` - Signed-in buyer's cart
- `/orders` - Signed-in buyer's orders
- `/orders/sales` - Signed-in seller's sales orders
- `/orders/checkout` - Checkout cart with payment and logistics details
- `/orders/show?id=1` - Order detail, timeline, payment, tracking, and fulfillment actions
- `/notifications` - In-app notification inbox
- `/notifications/preferences` - Email notification preferences
- `/wallet` - Signed-in user's store credit balance and ledger
- `/buylist` - Signed-in user's buylist, matched listings, and seller offers
- `/buylist/add?card_id=1` - Add card to buylist
- `/buylist/edit?id=1` - Edit buylist item
- `/buylist/offers/store?listing_id=1&wishlist_item_id=1` - Seller offers a matching listing to a buyer
- `/buylist/offers/accept?id=1` - Buyer accepts an offer and adds it to cart
- `/buylist/offers/decline?id=1` - Buyer declines an offer
- `/profile` - Edit current user's profile and location
- `/admin` - Admin dashboard
- `/admin/users` - Manage user roles
- `/cards` - Manage cards from the admin dashboard link
- `/admin/listings` - Manage and hide/cancel listings
- `/admin/orders` - Review marketplace orders and store credit payment breakdowns
- `/admin/prices` - Manage price entries
- `/admin/wallets` - Manage user store credit balances and ledger adjustments
- `/admin/reports` - Review and resolve marketplace reports
- `/admin/audit-logs` - Review immutable admin action history

If Apache rewrite is unavailable, use query-string routing:

```text
http://localhost/mtghub/public/index.php?url=login
```

## Implemented Phases

- Phase 1: Core MVC + authentication
- Phase 2: Card database
- Phase 3: Collection tracker
- Phase 4: Price tracker
- Phase 5: Marketplace listings
- Phase 6: Buylist matching
- Phase 7: Admin panel
- Phase 8: UI polish and dashboard

## Phase Roadmap

1. Core MVC + authentication
2. Card database
3. Collection tracker
4. Price tracker
5. Marketplace listings
6. Buylist matching
7. Admin panel
8. UI polish and dashboard

## Next Recommendations

The eight requested MVP phases are now implemented. Good next improvements:

- Add CSRF tokens to all POST forms
- Add pagination for listings, prices, and admin tables
- Add password change/reset
- Add listing contact preferences
- Add payment proof/upload and seller-buyer messaging for orders
- Add image upload support
- Add automated browser QA against a running XAMPP Apache instance

## Scryfall Bulk Sync

Admins can open `/cards/import` and use `Sync all cards` to download Scryfall's default card bulk data and upsert it into the local `cards` table. The sync stores Scryfall image URLs only; it does not download image files.

Use the optional test limit first, such as `100`, to confirm the server can download and import records before running the full sync.

## Card Search Performance

After syncing the full Scryfall database into an existing install, import this one-time optimization script in phpMyAdmin:

```text
database/optimize_cards_search.sql
```

It adds a full-text index for `card_name`, `set_name`, and `type_line`. MTGHub automatically uses that index for faster searches when it exists.
