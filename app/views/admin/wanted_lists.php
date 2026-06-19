<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Admin</p>
        <h1 class="h2 mb-0">Wanted Lists</h1>
    </div>
</div>

<?php if ($items === []): ?>
    <div class="archive-card p-4 text-center">
        <h2 class="h5">No wanted list items yet.</h2>
        <p class="text-muted mb-0">User wanted-list demand will appear here.</p>
    </div>
<?php else: ?>
    <div class="archive-card table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Card</th>
                    <th>Desired Qty</th>
                    <th>Max Price</th>
                    <th>Notes</th>
                    <th>Added</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= e($item['username']) ?><div class="text-muted small"><?= e($item['email']) ?></div></td>
                        <td>
                            <div class="fw-semibold"><?= e($item['card_name']) ?></div>
                            <div class="text-muted small"><?= e($item['set_name']) ?> #<?= e($item['collector_number']) ?></div>
                        </td>
                        <td><?= e((string) $item['desired_quantity']) ?></td>
                        <td><?= $item['max_price_php'] !== null ? 'PHP ' . e(number_format((float) $item['max_price_php'], 2)) : 'Any' ?></td>
                        <td class="text-muted small"><?= e((string) $item['notes']) ?></td>
                        <td><?= e($item['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
