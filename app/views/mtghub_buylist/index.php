<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Platform buylist</p>
        <h1 class="h2 mb-0">Sell to MTGHub</h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/my-sell-orders')) ?>">My Sell Orders</a>
</div>

<?php if ($entries === []): ?>
    <div class="archive-card p-4 text-center">
        <h2 class="h5">MTGHub is not buying any cards right now.</h2>
        <p class="text-muted mb-0">Check back later for new buylist openings.</p>
    </div>
<?php else: ?>
    <div class="archive-card table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Card</th>
                    <th>Accepted Condition</th>
                    <th>Cash Offer</th>
                    <th>Store Credit Offer</th>
                    <th>Target</th>
                    <th>Remaining</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($entry['card_name']) ?></div>
                            <div class="text-muted small"><?= e($entry['set_name'] ?: $entry['card_set_name']) ?> #<?= e($entry['collector_number']) ?></div>
                        </td>
                        <td><?= e($entry['accepted_condition'] ?: 'Any listed') ?></td>
                        <td>PHP <?= e(number_format((float) $entry['cash_offer'], 2)) ?></td>
                        <td>PHP <?= e(number_format((float) $entry['credit_offer'], 2)) ?></td>
                        <td><?= e((string) $entry['target_quantity']) ?></td>
                        <td><?= e((string) $entry['remaining_quantity']) ?></td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-archive" href="<?= e(url('/sell-to-mtghub/create?buylist_id=' . (int) $entry['id'])) ?>">Sell</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
