<div class="mb-4">
    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/cards')) ?>">Back to cards</a>
    <?php if (is_logged_in() && !is_admin()): ?>
        <a class="btn btn-sm btn-archive" href="<?= e(url('/collection/add?card_id=' . (int) $card['id'])) ?>">Add to collection</a>
        <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/buylist/add?card_id=' . (int) $card['id'])) ?>">Add to Wanted List</a>
        <?php if (can_trade() && !empty($mtghubBuylistEntry)): ?>
            <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/sell-to-mtghub/create?buylist_id=' . (int) $mtghubBuylistEntry['id'])) ?>">Sell to MTGHub</a>
        <?php endif; ?>
        <?php if (can_trade()): ?><a class="btn btn-sm btn-outline-dark" href="<?= e(url('/listings/create?card_id=' . (int) $card['id'])) ?>">Sell this card</a><?php else: ?><a class="btn btn-sm btn-outline-dark" href="<?= e(url('/verify-email')) ?>">Verify to sell</a><?php endif; ?>
    <?php elseif (!is_logged_in()): ?>
        <a class="btn btn-sm btn-archive" href="<?= e(url('/login')) ?>">Log in to collect</a>
    <?php endif; ?>
    <?php if (is_admin()): ?>
        <a class="btn btn-sm btn-archive" href="<?= e(url('/cards/edit?id=' . (int) $card['id'])) ?>">Edit card</a>
        <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/prices/create?card_id=' . (int) $card['id'])) ?>">Add price</a>
    <?php endif; ?>
</div>

<?php if (!empty($wantedListDemandCount) || !empty($mtghubBuylistEntry)): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="archive-card p-3 h-100">
                <div class="text-uppercase fw-semibold archive-kicker-dark small mb-1">Wanted List Signal</div>
                <div class="fw-semibold">Users want this card</div>
                <div class="text-muted small"><?= e((string) ($wantedListDemandCount ?? 0)) ?> wanted list request<?= (int) ($wantedListDemandCount ?? 0) === 1 ? '' : 's' ?></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="archive-card p-3 h-100">
                <div class="text-uppercase fw-semibold archive-kicker-dark small mb-1">MTGHub Buylist Signal</div>
                <?php if (!empty($mtghubBuylistEntry)): ?>
                    <div class="fw-semibold">MTGHub is buying this card</div>
                    <div class="text-muted small">Cash PHP <?= e(number_format((float) $mtghubBuylistEntry['cash_offer'], 2)) ?> | Credit PHP <?= e(number_format((float) $mtghubBuylistEntry['credit_offer'], 2)) ?></div>
                <?php else: ?>
                    <div class="fw-semibold">MTGHub is not buying this card right now</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-5 col-lg-4">
        <div class="archive-card p-3">
            <div class="card-image-wrap card-image-large">
                <?php if (!empty($card['image_url'])): ?>
                    <img src="<?= e($card['image_url']) ?>" alt="<?= e($card['card_name']) ?>">
                <?php else: ?>
                    <div class="card-image-placeholder">No Image</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-7 col-lg-8">
        <div class="archive-card p-4">
            <p class="text-uppercase fw-semibold archive-kicker-dark mb-1"><?= e(ucfirst($card['rarity'])) ?></p>
            <h1 class="h2"><?= e($card['card_name']) ?></h1>
            <p class="lead"><?= e($card['type_line']) ?></p>

            <dl class="row mb-0">
                <dt class="col-sm-4">Set</dt>
                <dd class="col-sm-8"><?= e($card['set_name']) ?></dd>
                <dt class="col-sm-4">Collector Number</dt>
                <dd class="col-sm-8"><?= e($card['collector_number']) ?></dd>
                <dt class="col-sm-4">Color</dt>
                <dd class="col-sm-8"><?= e($card['color'] ?: 'Colorless/Unspecified') ?></dd>
                <dt class="col-sm-4">Scryfall ID</dt>
                <dd class="col-sm-8"><?= e($card['scryfall_id'] ?: 'Not set') ?></dd>
                <dt class="col-sm-4">Latest PHP Price</dt>
                <dd class="col-sm-8">
                    <?php if ($latestPrice): ?>
                        PHP <?= e(number_format((float) $latestPrice['converted_php_price'], 2)) ?>
                        <span class="text-muted small">from <?= e($latestPrice['source_name']) ?> on <?= e($latestPrice['date_captured']) ?></span>
                    <?php else: ?>
                        Not tracked yet
                    <?php endif; ?>
                </dd>
            </dl>
        </div>
    </div>
</div>

<div class="archive-card p-4 mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
        <div>
            <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Marketplace</p>
            <h2 class="h4 mb-0">Active Listings</h2>
        </div>
        <a class="btn btn-sm btn-outline-secondary align-self-md-start" href="<?= e(url('/listings')) ?>">All listings</a>
    </div>

    <?php if ($activeListings === []): ?>
        <p class="text-muted mb-0">No active marketplace listings for this card yet.</p>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($activeListings as $listing): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="market-listing p-3 h-100">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="fw-semibold">PHP <?= e(number_format((float) $listing['price_php'], 2)) ?></div>
                            <span class="badge text-bg-light"><?= e(ucfirst($listing['status'])) ?></span>
                        </div>
                        <div class="small text-muted mb-2">Seller: <?= e($listing['username']) ?></div>
                        <div class="small">Qty <?= e((string) $listing['quantity']) ?> | <?= e(ucwords(str_replace('_', ' ', $listing['card_condition']))) ?></div>
                        <div class="small">Location: <?= e($listing['seller_location']) ?></div>
                        <div class="small text-muted">Delivery: <?= e($listing['delivery_options']) ?></div>
                        <?php if (is_logged_in() && !is_admin() && (int) $listing['user_id'] !== (int) current_user()['id'] && (int) $listing['quantity'] > 0): ?>
                            <form class="d-flex gap-2 mt-3" method="post" action="<?= e(url('/cart/add?listing_id=' . (int) $listing['id'])) ?>">
                                <input class="form-control form-control-sm" name="quantity" type="number" min="1" max="<?= e((string) $listing['quantity']) ?>" value="1" style="width: 90px" required>
                                <button class="btn btn-sm btn-archive" type="submit">Add to cart</button>
                            </form>
                        <?php elseif (!is_logged_in() && (int) $listing['quantity'] > 0): ?>
                            <a class="btn btn-sm btn-archive mt-3" href="<?= e(url('/login')) ?>">Log in to buy</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="archive-card p-4 mt-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
        <div>
            <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Price history</p>
            <h2 class="h4 mb-0">Manual Philippine Price Tracker</h2>
        </div>
        <a class="btn btn-sm btn-outline-secondary align-self-md-start" href="<?= e(url('/prices')) ?>">All prices</a>
    </div>

    <?php if ($priceHistory === []): ?>
        <p class="text-muted mb-0">No price history has been entered for this card.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Source</th>
                        <th>Original</th>
                        <th>PHP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($priceHistory as $price): ?>
                        <tr>
                            <td><?= e($price['date_captured']) ?></td>
                            <td><?= e($price['source_name']) ?></td>
                            <td><?= e($price['currency']) ?> <?= e(number_format((float) $price['price'], 2)) ?></td>
                            <td class="fw-semibold">PHP <?= e(number_format((float) $price['converted_php_price'], 2)) ?></td>
                        </tr>
                        <?php if (!empty($price['notes'])): ?>
                            <tr class="collection-notes-row">
                                <td colspan="4" class="text-muted small">Notes: <?= e($price['notes']) ?></td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
