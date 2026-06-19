<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Admin</p>
        <h1 class="h2 mb-0">MTGHub Buylist</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(url('/admin/mtghub-buylist/orders')) ?>">Orders</a>
        <a class="btn btn-archive" href="<?= e(url('/admin/mtghub-buylist/create')) ?>">Add entry</a>
    </div>
</div>

<div class="archive-card table-responsive">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Card</th>
                <th>Cash</th>
                <th>Credit</th>
                <th>Target</th>
                <th>Received</th>
                <th>Remaining</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($entries as $entry): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($entry['card_name']) ?></div>
                        <div class="text-muted small"><?= e($entry['set_name'] ?: $entry['card_set_name']) ?> #<?= e($entry['collector_number']) ?></div>
                    </td>
                    <td>PHP <?= e(number_format((float) $entry['cash_offer'], 2)) ?></td>
                    <td>PHP <?= e(number_format((float) $entry['credit_offer'], 2)) ?></td>
                    <td><?= e((string) $entry['target_quantity']) ?></td>
                    <td><?= e((string) $entry['received_quantity']) ?></td>
                    <td><?= e((string) $entry['remaining_quantity']) ?></td>
                    <td><span class="badge <?= (int) $entry['is_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $entry['is_active'] === 1 ? 'Active' : 'Inactive' ?></span></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/admin/mtghub-buylist/edit?id=' . (int) $entry['id'])) ?>">Edit</a>
                        <form class="d-inline" method="post" action="<?= e(url('/admin/mtghub-buylist/toggle?id=' . (int) $entry['id'])) ?>">
                            <button class="btn btn-sm btn-outline-secondary" type="submit">Toggle</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
