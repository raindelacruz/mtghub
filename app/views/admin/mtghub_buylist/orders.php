<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Admin</p>
        <h1 class="h2 mb-0">MTGHub Buylist Orders</h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/mtghub-buylist')) ?>">Buylist entries</a>
</div>

<div class="archive-card table-responsive">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Order</th>
                <th>User</th>
                <th>Status</th>
                <th>Payout</th>
                <th>Estimated</th>
                <th>Approved</th>
                <th>Submitted</th>
                <th class="text-end">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= e((string) $order['id']) ?></td>
                    <td><?= e($order['username']) ?><div class="text-muted small"><?= e($order['email']) ?></div></td>
                    <td><span class="badge text-bg-light"><?= e(ucwords(str_replace('_', ' ', $order['status']))) ?></span></td>
                    <td><?= e(ucwords(str_replace('_', ' ', $order['payout_method']))) ?></td>
                    <td>PHP <?= e(number_format((float) $order['estimated_total'], 2)) ?></td>
                    <td>PHP <?= e(number_format((float) $order['approved_total'], 2)) ?></td>
                    <td><?= e($order['created_at']) ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?= e(url('/admin/mtghub-buylist/orders/view?id=' . (int) $order['id'])) ?>">View</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
