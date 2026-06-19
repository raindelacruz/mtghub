<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Admin</p>
        <h1 class="h2 mb-0">Manage Orders</h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin')) ?>">Dashboard</a>
</div>

<?php if ($orders === []): ?>
    <div class="archive-card p-4 text-center">
        <h2 class="h5">No orders yet.</h2>
        <p class="text-muted mb-0">Marketplace orders will appear here after checkout.</p>
    </div>
<?php else: ?>
    <div class="archive-card table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Buyer</th>
                    <th>Seller</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold">Order #<?= e((string) $order['id']) ?></div>
                            <div class="text-muted small"><?= e($order['cards_summary'] ?: 'Legacy order') ?></div>
                            <?php if (!empty($order['payment_reference'])): ?>
                                <div class="text-muted small">Payment ref: <?= e($order['payment_reference']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= e($order['buyer_username']) ?></td>
                        <td><?= e($order['seller_username']) ?></td>
                        <td>
                            <div>₱<?= e(number_format((float) $order['total_price_php'], 2)) ?></div>
                            <div class="text-muted small">Store Credit Used: ₱<?= e(number_format((float) ($order['store_credit_used'] ?? 0), 2)) ?></div>
                            <div class="text-muted small">Cash Amount Due: ₱<?= e(number_format((float) ($order['cash_amount_due'] ?? $order['total_price_php']), 2)) ?></div>
                            <div class="text-muted small">Payment Method: <?= e(ucwords(str_replace('_', ' ', $order['payment_method']))) ?></div>
                            <?php if ((float) ($order['store_credit_used'] ?? 0) > 0): ?>
                                <div class="text-muted small">Seller Credit Settlement: <?= (int) ($order['store_credit_settled'] ?? 0) === 1 ? 'Settled' : 'Pending completion' ?></div>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge text-bg-light"><?= e(ucwords(str_replace('_', ' ', $order['status']))) ?></span><?php if ($order['status'] === 'disputed'): ?><div><a class="btn btn-sm btn-outline-danger mt-2" href="<?= e(url('/admin/orders/messages?id=' . (int) $order['id'])) ?>">Review messages</a></div><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
