# MTGHub Buylist and Wanted List Integration

Last updated: 2026-06-16

## Program Difference

MTGHub now separates two programs that previously shared confusing language.

Wanted List is the existing user-to-user buying flow:

```text
User wants to buy cards.
System matches wanted cards with marketplace listings.
Seller may send an offer.
Buyer may accept the offer.
Accepted offer goes to cart.
Checkout remains unchanged.
```

MTGHub Buylist is the new platform-to-user acquisition flow:

```text
MTGHub posts cards it wants to buy.
Users sell cards directly to MTGHub.
Users choose cash or store credit.
Admin receives, inspects, and approves the submission.
Store credit is credited only after admin approval.
```

The existing database tables `wishlist_items` and `buylist_offers` remain in place for the Wanted List flow. They were not renamed.

## User Workflow

Wanted List:

1. User opens a card and chooses `Add to Wanted List`.
2. `/buylist` and `/wanted-list` show wanted cards, matching listings, and seller offers.
3. Seller offers can still be accepted into cart.
4. Checkout remains the existing cart and order workflow.

MTGHub Buylist:

1. User opens `/sell-to-mtghub`.
2. User reviews active MTGHub buylist entries with cash and store credit offers.
3. User submits a sell order with quantity, declared condition, payout method, and remarks.
4. User tracks submissions at `/my-sell-orders`.
5. User can view status, approved payout, and admin remarks.

## Admin Workflow

1. Admin creates buylist entries at `/admin/mtghub-buylist/create`.
2. Admin sets card, display set, accepted condition, cash offer, store credit offer, target quantity, active status, and notes.
3. Admin reviews submitted sell orders at `/admin/mtghub-buylist/orders`.
4. Admin marks an order received.
5. Admin starts inspection.
6. Admin approves full or partial quantities, or rejects the order.
7. Cash orders are completed when admin marks cash payout completed.
8. Store credit orders are credited and completed after approval.

## Store Credit Workflow

The MTGHub Buylist uses the existing `wallets` and `wallet_transactions` tables.

Store credit payout rules:

- Store credit is credited only after admin approval.
- Duplicate credits are blocked with `mtghub_buylist_orders.store_credit_credited`.
- Credits use wallet transaction type `credit_buylist_settlement`.
- Ledger rows use reference type `mtghub_buylist_order`.
- Ledger notes reference the order ID, for example `MTGHub Buylist settlement for Order #123`.
- Wallet balance protection remains in `Wallet::changeBalance()`, including the no-negative-balance rule.

## Routes Added

User routes:

```text
/sell-to-mtghub
/sell-to-mtghub/create?buylist_id=...
/sell-to-mtghub/store
/my-sell-orders
/my-sell-orders/view?id=...
/wanted-list
/wanted-list/add
/wanted-list/store
```

Admin routes:

```text
/admin/wanted-lists
/admin/mtghub-buylist
/admin/mtghub-buylist/create
/admin/mtghub-buylist/store
/admin/mtghub-buylist/edit?id=...
/admin/mtghub-buylist/update?id=...
/admin/mtghub-buylist/toggle?id=...
/admin/mtghub-buylist/orders
/admin/mtghub-buylist/orders/view?id=...
/admin/mtghub-buylist/orders/receive?id=...
/admin/mtghub-buylist/orders/inspect?id=...
/admin/mtghub-buylist/orders/approve?id=...
/admin/mtghub-buylist/orders/reject?id=...
/admin/mtghub-buylist/orders/complete?id=...
```

Preserved compatibility routes:

```text
/buylist
/buylist/add
/buylist/store
/buylist/edit
/buylist/update
/buylist/delete
/buylist/offers/store
/buylist/offers/accept
/buylist/offers/decline
```

## Database Tables Added

Migration:

```text
database/add_mtghub_platform_buylist.sql
```

Tables:

```text
mtghub_buylist_entries
mtghub_buylist_orders
mtghub_buylist_order_items
```

The same tables are also included in `database/schema.sql` for fresh installs.

## Testing Steps

Wanted List regression:

1. User adds wanted card through `/buylist/add?card_id=...`.
2. `/buylist` displays it as Wanted List.
3. Seller sends offer from matching active listing.
4. Buyer accepts offer.
5. Accepted offer enters cart.
6. Checkout still works.

MTGHub Buylist cash payout:

1. Admin creates MTGHub Buylist entry.
2. User opens `/sell-to-mtghub`.
3. User submits sell order using cash payout.
4. Admin receives and approves order.
5. Admin marks cash payout completed.
6. Order status becomes completed.

MTGHub Buylist store credit payout:

1. User submits sell order using store credit payout.
2. Admin receives and approves order.
3. Store credit is credited once.
4. `wallets.store_credit_balance` increases correctly.
5. `wallet_transactions` records the credit.
6. Repeating approval does not duplicate credit.

Security note: this project still does not include a full CSRF framework. Add CSRF tokens to POST forms before production use.
