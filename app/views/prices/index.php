<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Phase 4</p>
        <h1 class="h2 mb-0">Philippine Price Tracker</h1>
    </div>
    <a class="btn btn-archive" href="<?= e(url('/cards')) ?>">Choose a card</a>
</div>

<?php if ($prices === []): ?>
    <div class="archive-card p-4 text-center">
        <h2 class="h5">No price entries yet.</h2>
        <p class="text-muted mb-0">Admins can add manual price entries from a card detail page.</p>
    </div>
<?php else: ?>
    <div class="archive-card table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Card</th>
                    <th>Source</th>
                    <th>Original</th>
                    <th>PHP</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($prices as $price): ?>
                    <tr>
                        <td>
                            <a class="fw-semibold text-dark" href="<?= e(url('/cards/show?id=' . (int) $price['card_id'])) ?>"><?= e($price['card_name']) ?></a>
                            <div class="text-muted small"><?= e($price['set_name']) ?> #<?= e($price['collector_number']) ?></div>
                        </td>
                        <td><?= e($price['source_name']) ?></td>
                        <td><?= e($price['currency']) ?> <?= e(number_format((float) $price['price'], 2)) ?></td>
                        <td class="fw-semibold">PHP <?= e(number_format((float) $price['converted_php_price'], 2)) ?></td>
                        <td><?= e($price['date_captured']) ?></td>
                    </tr>
                    <?php if (!empty($price['notes'])): ?>
                        <tr class="collection-notes-row">
                            <td colspan="5" class="text-muted small">Notes: <?= e($price['notes']) ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
