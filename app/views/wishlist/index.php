<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Buyer demand</p>
        <h1 class="h2 mb-0">My Wanted List</h1>
    </div>
    <a class="btn btn-archive" href="<?= e(url('/cards')) ?>">Find cards</a>
</div>

<div class="archive-card p-4 mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
        <div>
            <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Seller Offers</p>
            <h2 class="h4 mb-0">Offers from matching sellers</h2>
        </div>
    </div>

    <?php if ($offers === []): ?>
        <p class="text-muted mb-0">No seller offers yet. Sellers with matching active listings can offer cards from your wanted list.</p>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($offers as $offer): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="market-listing p-3 h-100">
                        <div class="d-flex justify-content-between gap-2">
                            <div class="fw-semibold"><?= e($offer['card_name']) ?></div>
                            <span class="badge text-bg-light"><?= e(ucfirst($offer['status'])) ?></span>
                        </div>
                        <div class="text-muted small mb-2"><?= e($offer['set_name']) ?> #<?= e($offer['collector_number']) ?></div>
                        <div class="market-price mb-2">PHP <?= e(number_format((float) $offer['price_php'], 2)) ?></div>
                        <div class="small">Seller: <?= e($offer['seller_username']) ?></div>
                        <div class="small">Offered qty: <?= e((string) $offer['quantity']) ?> | Available: <?= e((string) $offer['listing_quantity']) ?></div>
                        <div class="small">Condition: <?= e(ucwords(str_replace('_', ' ', $offer['card_condition']))) ?></div>
                        <div class="small">Location: <?= e($offer['seller_location']) ?></div>
                        <div class="small text-muted">Delivery: <?= e($offer['delivery_options']) ?></div>
                        <?php if (!empty($offer['message'])): ?>
                            <div class="small text-muted mt-2">Message: <?= e($offer['message']) ?></div>
                        <?php endif; ?>
                        <?php if ($offer['status'] === 'pending' && $offer['listing_status'] === 'active' && (int) $offer['listing_quantity'] > 0): ?>
                            <div class="d-flex gap-2 mt-3">
                                <form method="post" action="<?= e(url('/buylist/offers/accept?id=' . (int) $offer['id'])) ?>">
                                    <button class="btn btn-sm btn-archive" type="submit">Add to cart</button>
                                </form>
                                <form method="post" action="<?= e(url('/buylist/offers/decline?id=' . (int) $offer['id'])) ?>">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">Decline</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="archive-card p-4 mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
        <div>
            <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Marketplace matches</p>
            <h2 class="h4 mb-0">Wanted List Matches</h2>
        </div>
        <a class="btn btn-sm btn-outline-secondary align-self-md-start" href="<?= e(url('/listings')) ?>">All listings</a>
    </div>

    <?php if ($matches === []): ?>
        <p class="text-muted mb-0">No active marketplace listings currently match your wanted list and max prices.</p>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($matches as $match): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="market-listing p-3 h-100">
                        <div class="fw-semibold"><?= e($match['card_name']) ?></div>
                        <div class="text-muted small mb-2"><?= e($match['set_name']) ?> #<?= e($match['collector_number']) ?></div>
                        <div class="market-price mb-2">PHP <?= e(number_format((float) $match['price_php'], 2)) ?></div>
                        <div class="small">Seller: <?= e($match['username']) ?></div>
                        <div class="small">Qty available: <?= e((string) $match['listing_quantity']) ?> | Wanted: <?= e((string) $match['desired_quantity']) ?></div>
                        <div class="small">Condition: <?= e(ucwords(str_replace('_', ' ', $match['card_condition']))) ?></div>
                        <div class="small">Location: <?= e($match['seller_location']) ?></div>
                        <div class="small text-muted">Delivery: <?= e($match['delivery_options']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($items === []): ?>
    <div class="archive-card p-4 text-center">
        <h2 class="h5">Your wanted list is empty.</h2>
        <p class="text-muted">Open a card detail page and add cards you are actively looking to buy.</p>
        <a class="btn btn-archive" href="<?= e(url('/cards')) ?>">Browse cards</a>
    </div>
<?php else: ?>
    <div class="archive-card table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Card</th>
                    <th>Buying</th>
                    <th>Max Buy Price</th>
                    <th>Notes</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($item['card_name']) ?></div>
                            <div class="text-muted small"><?= e($item['set_name']) ?> #<?= e($item['collector_number']) ?></div>
                        </td>
                        <td><?= e((string) $item['desired_quantity']) ?></td>
                        <td><?= $item['max_price_php'] !== null ? 'PHP ' . e(number_format((float) $item['max_price_php'], 2)) : 'Any' ?></td>
                        <td class="text-muted small"><?= e($item['notes'] ?? '') ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/buylist/edit?id=' . (int) $item['id'])) ?>">Edit</a>
                            <form class="d-inline" method="post" action="<?= e(url('/buylist/delete?id=' . (int) $item['id'])) ?>" onsubmit="return confirm('Remove this card from your wanted list?');">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
