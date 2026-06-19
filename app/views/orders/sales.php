<?php $statusLabel = static fn (string $status): string => ucwords(str_replace('_', ' ', $status)); ?>
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div><p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Seller tools</p><h1 class="h2 mb-0">Sales Orders</h1></div>
    <div class="d-flex gap-2"><a class="btn btn-outline-dark" href="<?= e(url('/orders')) ?>">My orders</a><a class="btn btn-archive" href="<?= e(url('/listings/mine')) ?>">My listings</a></div>
</div>
<?php if ($orders === []): ?><div class="archive-card p-4 text-center"><h2 class="h5">No sales orders yet.</h2><a class="btn btn-archive" href="<?= e(url('/listings/mine')) ?>">View listings</a></div><?php else: ?>
<div class="archive-card table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Order</th><th>Buyer</th><th>Total</th><th>Logistics</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($orders as $order): ?><tr><td><div class="fw-semibold">Order #<?= e((string) $order['id']) ?></div><div class="small text-muted"><?= e($order['cards_summary'] ?: 'Order items') ?></div></td><td><?= e($order['buyer_username']) ?></td><td>₱<?= e(number_format((float) $order['total_price_php'], 2)) ?></td><td><?= e($order['logistics_method'] === 'lbc' ? 'LBC shipping' : 'Meetup') ?></td><td><span class="badge text-bg-light"><?= e($statusLabel($order['status'])) ?></span></td><td class="text-end"><a class="btn btn-sm btn-archive" href="<?= e(url('/orders/show?id=' . (int) $order['id'])) ?>">Manage</a></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
