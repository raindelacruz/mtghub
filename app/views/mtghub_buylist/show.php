<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">My Sell Orders</p>
        <h1 class="h2 mb-0">Sell Order #<?= e((string) $order['id']) ?></h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/my-sell-orders')) ?>">Back</a>
</div>

<div class="archive-card p-4 mb-4">
    <div class="row g-3">
        <div class="col-md-3"><strong>Status:</strong> <?= e(ucwords(str_replace('_', ' ', $order['status']))) ?></div>
        <div class="col-md-3"><strong>Payout:</strong> <?= e(ucwords(str_replace('_', ' ', $order['payout_method']))) ?></div>
        <div class="col-md-3"><strong>Estimated:</strong> PHP <?= e(number_format((float) $order['estimated_total'], 2)) ?></div>
        <div class="col-md-3"><strong>Approved:</strong> PHP <?= e(number_format((float) $order['approved_total'], 2)) ?></div>
    </div>
    <?php if (!empty($order['admin_remarks'])): ?>
        <div class="text-muted small mt-3">Admin remarks: <?= e($order['admin_remarks']) ?></div>
    <?php endif; ?>
</div>

<div class="archive-card table-responsive">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Card</th>
                <th>Submitted</th>
                <th>Accepted</th>
                <th>Declared</th>
                <th>Approved</th>
                <th>Subtotal</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order['items'] as $item): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($item['card_name']) ?></div>
                        <div class="text-muted small"><?= e($item['set_name']) ?> #<?= e($item['collector_number']) ?></div>
                    </td>
                    <td><?= e((string) $item['quantity_submitted']) ?></td>
                    <td><?= e((string) $item['quantity_accepted']) ?></td>
                    <td><?= e(ucwords(str_replace('_', ' ', (string) $item['declared_condition']))) ?></td>
                    <td><?= e(ucwords(str_replace('_', ' ', (string) $item['approved_condition']))) ?></td>
                    <td>PHP <?= e(number_format((float) $item['approved_subtotal'], 2)) ?></td>
                    <td><?= e(ucwords(str_replace('_', ' ', $item['status']))) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
