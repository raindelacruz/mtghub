<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Buyer tools</p>
        <h1 class="h2 mb-0">Cart</h1>
    </div>
    <a class="btn btn-archive" href="<?= e(url('/listings')) ?>">Continue shopping</a>
</div>

<?php if ($items === []): ?>
    <div class="archive-card p-4 text-center">
        <h2 class="h5">Your cart is empty.</h2>
        <p class="text-muted">Add active marketplace listings to buy multiple cards from one seller.</p>
        <a class="btn btn-archive" href="<?= e(url('/listings')) ?>">Browse listings</a>
    </div>
<?php else: ?>
    <?php if ($summary['seller_count'] > 1): ?>
        <div class="alert alert-warning">Checkout supports one seller at a time. Remove items from other sellers before checking out.</div>
    <?php endif; ?>

    <div class="archive-card table-responsive mb-4">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Card</th>
                    <th>Seller</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Line total</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($item['card_name']) ?></div>
                            <div class="text-muted small"><?= e($item['set_name']) ?> #<?= e($item['collector_number']) ?></div>
                            <div class="text-muted small"><?= e(ucwords(str_replace('_', ' ', $item['card_condition']))) ?></div>
                            <?php if ($item['listing_status'] !== 'active'): ?>
                                <div class="text-danger small">This listing is no longer active.</div>
                            <?php elseif ((int) $item['quantity'] > (int) $item['available_quantity']): ?>
                                <div class="text-danger small">Only <?= e((string) $item['available_quantity']) ?> available.</div>
                            <?php endif; ?>
                        </td>
                        <td><?= e($item['seller_username']) ?></td>
                        <td>PHP <?= e(number_format((float) $item['price_php'], 2)) ?></td>
                        <td>
                            <form class="d-flex gap-2" method="post" action="<?= e(url('/cart/update?id=' . (int) $item['id'])) ?>">
                                <input class="form-control form-control-sm" name="quantity" type="number" min="1" max="<?= e((string) $item['available_quantity']) ?>" value="<?= e((string) $item['quantity']) ?>" style="width: 90px" required>
                                <button class="btn btn-sm btn-outline-dark" type="submit">Update</button>
                            </form>
                        </td>
                        <td class="fw-semibold">PHP <?= e(number_format((float) $item['price_php'] * (int) $item['quantity'], 2)) ?></td>
                        <td class="text-end">
                            <form method="post" action="<?= e(url('/cart/delete?id=' . (int) $item['id'])) ?>">
                                <button class="btn btn-sm btn-outline-secondary" type="submit">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="archive-card p-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="text-muted small text-uppercase fw-semibold">Cart subtotal</div>
                <div class="market-price">PHP <?= e(number_format((float) $summary['subtotal'], 2)) ?></div>
                <div class="small text-muted"><?= e((string) $summary['quantity']) ?> total card(s)</div>
            </div>
            <div class="d-flex gap-2">
                <form method="post" action="<?= e(url('/cart/clear')) ?>" onsubmit="return confirm('Clear your cart?');">
                    <button class="btn btn-outline-secondary" type="submit">Clear cart</button>
                </form>
                <a class="btn btn-archive <?= $summary['seller_count'] > 1 ? 'disabled' : '' ?>" href="<?= e(url('/orders/checkout')) ?>">Checkout</a>
            </div>
        </div>
    </div>
<?php endif; ?>
