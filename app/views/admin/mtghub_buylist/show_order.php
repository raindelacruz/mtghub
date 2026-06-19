<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Admin</p>
        <h1 class="h2 mb-0">MTGHub Buylist Order #<?= e((string) $order['id']) ?></h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin/mtghub-buylist/orders')) ?>">Back</a>
</div>

<div class="archive-card p-4 mb-4">
    <div class="row g-3">
        <div class="col-md-3"><strong>User:</strong> <?= e($order['username']) ?></div>
        <div class="col-md-3"><strong>Status:</strong> <?= e(ucwords(str_replace('_', ' ', $order['status']))) ?></div>
        <div class="col-md-3"><strong>Payout:</strong> <?= e(ucwords(str_replace('_', ' ', $order['payout_method']))) ?></div>
        <div class="col-md-3"><strong>Approved:</strong> PHP <?= e(number_format((float) $order['approved_total'], 2)) ?></div>
    </div>
    <?php if (!empty($order['user_remarks'])): ?>
        <div class="text-muted small mt-3">User remarks: <?= e($order['user_remarks']) ?></div>
    <?php endif; ?>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    <?php if ($order['status'] === 'pending_receipt'): ?>
        <form method="post" action="<?= e(url('/admin/mtghub-buylist/orders/receive?id=' . (int) $order['id'])) ?>">
            <button class="btn btn-archive" type="submit">Mark received</button>
        </form>
    <?php endif; ?>
    <?php if (in_array($order['status'], ['pending_receipt', 'received'], true)): ?>
        <form method="post" action="<?= e(url('/admin/mtghub-buylist/orders/inspect?id=' . (int) $order['id'])) ?>">
            <button class="btn btn-outline-dark" type="submit">Start inspection</button>
        </form>
    <?php endif; ?>
    <?php if ($order['payout_method'] === 'cash' && in_array($order['status'], ['accepted', 'partially_accepted'], true)): ?>
        <form class="d-flex gap-2" method="post" action="<?= e(url('/admin/mtghub-buylist/orders/complete?id=' . (int) $order['id'])) ?>">
            <input class="form-control form-control-sm" name="admin_remarks" placeholder="Cash payout note">
            <button class="btn btn-archive" type="submit">Complete cash payout</button>
        </form>
    <?php endif; ?>
</div>

<form method="post" action="<?= e(url('/admin/mtghub-buylist/orders/approve?id=' . (int) $order['id'])) ?>">
    <div class="archive-card table-responsive mb-4">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Card</th>
                    <th>Submitted</th>
                    <th>Accepted Qty</th>
                    <th>Condition</th>
                    <th>Item Remarks</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($item['card_name']) ?></div>
                            <div class="text-muted small"><?= e($item['set_name']) ?> #<?= e($item['collector_number']) ?></div>
                            <div class="text-muted small">Cash PHP <?= e(number_format((float) $item['cash_offer_snapshot'], 2)) ?> | Credit PHP <?= e(number_format((float) $item['credit_offer_snapshot'], 2)) ?></div>
                        </td>
                        <td><?= e((string) $item['quantity_submitted']) ?></td>
                        <td style="width: 140px">
                            <input class="form-control form-control-sm" name="items[<?= e((string) $item['id']) ?>][quantity_accepted]" type="number" min="0" max="<?= e((string) $item['quantity_submitted']) ?>" value="<?= e((string) ($item['quantity_accepted'] ?: $item['quantity_submitted'])) ?>">
                        </td>
                        <td style="width: 190px">
                            <select class="form-select form-select-sm" name="items[<?= e((string) $item['id']) ?>][approved_condition]">
                                <?php foreach (['near_mint', 'lightly_played', 'moderately_played', 'heavily_played', 'damaged'] as $condition): ?>
                                    <option value="<?= e($condition) ?>" <?= (($item['approved_condition'] ?: $item['declared_condition']) === $condition) ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $condition))) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input class="form-control form-control-sm" name="items[<?= e((string) $item['id']) ?>][admin_remarks]" value="<?= e((string) $item['admin_remarks']) ?>"></td>
                        <td><?= e(ucwords(str_replace('_', ' ', $item['status']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="archive-card p-4">
        <label class="form-label" for="admin_remarks">Admin remarks</label>
        <textarea class="form-control" id="admin_remarks" name="admin_remarks" rows="3"><?= e((string) $order['admin_remarks']) ?></textarea>
        <div class="d-flex gap-2 mt-3">
            <?php if (in_array($order['status'], ['received', 'under_inspection'], true)): ?>
                <button class="btn btn-archive" type="submit">Approve inspected quantities</button>
            <?php endif; ?>
        </div>
    </div>
</form>

<?php if (in_array($order['status'], ['pending_receipt', 'received', 'under_inspection'], true)): ?>
    <form class="mt-3" method="post" action="<?= e(url('/admin/mtghub-buylist/orders/reject?id=' . (int) $order['id'])) ?>" onsubmit="return confirm('Reject this sell order?');">
        <input type="hidden" name="admin_remarks" value="Rejected by admin">
        <button class="btn btn-outline-danger" type="submit">Reject order</button>
    </form>
<?php endif; ?>
