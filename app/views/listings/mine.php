<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Seller tools</p>
        <h1 class="h2 mb-0">My Listings</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-dark" href="<?= e(url('/orders/sales')) ?>">Sales orders</a>
        <a class="btn btn-archive" href="<?= e(url('/cards')) ?>">List another card</a>
    </div>
</div>

<div class="archive-card p-4 mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
        <div>
            <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Wanted List demand</p>
            <h2 class="h4 mb-0">Buyers may want your active cards</h2>
        </div>
        <a class="btn btn-sm btn-outline-secondary align-self-md-start" href="<?= e(url('/listings')) ?>">Marketplace</a>
    </div>

    <?php if ($buylistDemand === []): ?>
        <p class="text-muted mb-0">No current wanted list requests match your active listings.</p>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($buylistDemand as $demand): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="market-listing p-3 h-100">
                        <div class="fw-semibold"><?= e($demand['card_name']) ?></div>
                        <div class="text-muted small mb-2"><?= e($demand['set_name']) ?> #<?= e($demand['collector_number']) ?></div>
                        <div class="market-price mb-2">Your price: PHP <?= e(number_format((float) $demand['price_php'], 2)) ?></div>
                        <div class="small">Buyer: <?= e($demand['buyer_username']) ?> from <?= e($demand['buyer_city']) ?>, <?= e($demand['buyer_province']) ?></div>
                        <div class="small">Buyer wants: <?= e((string) $demand['desired_quantity']) ?> | You have: <?= e((string) $demand['listing_quantity']) ?></div>
                        <div class="small">Buyer max: <?= $demand['max_price_php'] !== null ? 'PHP ' . e(number_format((float) $demand['max_price_php'], 2)) : 'Any' ?></div>
                        <div class="small">Condition: <?= e(ucwords(str_replace('_', ' ', $demand['card_condition']))) ?></div>
                        <?php if (!empty($demand['buyer_notes'])): ?>
                            <div class="small text-muted mt-2">Buyer notes: <?= e($demand['buyer_notes']) ?></div>
                        <?php endif; ?>
                        <?php if ($demand['max_price_php'] === null || (float) $demand['price_php'] <= (float) $demand['max_price_php']): ?>
                            <form class="mt-3" method="post" action="<?= e(url('/buylist/offers/store?listing_id=' . (int) $demand['listing_id'] . '&wishlist_item_id=' . (int) $demand['wishlist_item_id'])) ?>">
                                <input class="form-control form-control-sm mb-2" name="message" maxlength="255" placeholder="Optional message to buyer">
                                <button class="btn btn-sm btn-archive" type="submit">Offer to buyer</button>
                            </form>
                        <?php else: ?>
                            <div class="small text-muted mt-3">Your price is above this buyer's max. Edit the listing price before sending an offer.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($listings === []): ?>
    <div class="archive-card p-4 text-center">
        <h2 class="h5">You have no listings yet.</h2>
        <p class="text-muted">Browse the card database and create a listing from a card detail page.</p>
        <a class="btn btn-archive" href="<?= e(url('/cards')) ?>">Browse cards</a>
    </div>
<?php else: ?>
    <div class="archive-card table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Card</th>
                    <th>Qty</th>
                    <th>Condition</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listings as $listing): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($listing['card_name']) ?></div>
                            <div class="text-muted small"><?= e($listing['set_name']) ?> #<?= e($listing['collector_number']) ?></div>
                        </td>
                        <td><?= e((string) $listing['quantity']) ?></td>
                        <td><?= e(ucwords(str_replace('_', ' ', $listing['card_condition']))) ?></td>
                        <td>PHP <?= e(number_format((float) $listing['price_php'], 2)) ?></td>
                        <td><span class="badge text-bg-light"><?= e(ucfirst($listing['status'])) ?></span></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/listings/edit?id=' . (int) $listing['id'])) ?>">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
