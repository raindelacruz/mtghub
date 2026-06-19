<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Phase 3</p>
        <h1 class="h2 mb-0">My Collection</h1>
    </div>
    <a class="btn btn-archive" href="<?= e(url('/cards')) ?>">Add cards</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="archive-card p-4">
            <div class="text-muted small text-uppercase fw-semibold">Total cards</div>
            <div class="display-6 fw-bold"><?= e((string) (int) $totals['total_cards']) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="archive-card p-4">
            <div class="text-muted small text-uppercase fw-semibold">Acquisition total</div>
            <div class="display-6 fw-bold">PHP <?= e(number_format((float) $totals['acquisition_total'], 2)) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="archive-card p-4">
            <div class="text-muted small text-uppercase fw-semibold">Estimated market value</div>
            <div class="display-6 fw-bold">PHP <?= e(number_format((float) $totals['estimated_market_total'], 2)) ?></div>
        </div>
    </div>
</div>

<?php if ($items === []): ?>
    <div class="archive-card p-4 text-center">
        <h2 class="h5">Your collection is empty.</h2>
        <p class="text-muted">Browse the card database and add cards you own.</p>
        <a class="btn btn-archive" href="<?= e(url('/cards')) ?>">Browse cards</a>
    </div>
<?php else: ?>
    <div class="archive-card table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Card</th>
                    <th>Qty</th>
                    <th>Condition</th>
                    <th>Language</th>
                    <th>Finish</th>
                    <th>Acquired</th>
                    <th>Latest PHP</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= e($item['card_name']) ?></div>
                            <div class="text-muted small"><?= e($item['set_name']) ?> #<?= e($item['collector_number']) ?></div>
                        </td>
                        <td><?= e((string) $item['quantity']) ?></td>
                        <td><?= e(ucwords(str_replace('_', ' ', $item['card_condition']))) ?></td>
                        <td><?= e($item['language']) ?></td>
                        <td><?= $item['is_foil'] ? 'Foil' : 'Non-foil' ?></td>
                        <td>PHP <?= e(number_format((float) $item['acquisition_price'], 2)) ?></td>
                        <td>
                            <?= $item['latest_php_price'] !== null ? 'PHP ' . e(number_format((float) $item['latest_php_price'], 2)) : 'Not tracked' ?>
                        </td>
                        <td class="text-end">
                            <?php if (!empty($mtghubBuylistEntriesByCard[(int) $item['card_id']])): ?>
                                <a class="btn btn-sm btn-archive" href="<?= e(url('/sell-to-mtghub/create?buylist_id=' . (int) $mtghubBuylistEntriesByCard[(int) $item['card_id']]['id'])) ?>">Sell to MTGHub</a>
                            <?php endif; ?>
                            <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/collection/edit?id=' . (int) $item['id'])) ?>">Edit</a>
                            <form class="d-inline" method="post" action="<?= e(url('/collection/delete?id=' . (int) $item['id'])) ?>" onsubmit="return confirm('Remove this card from your collection?');">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php if (!empty($item['notes'])): ?>
                        <tr class="collection-notes-row">
                            <td colspan="8" class="text-muted small">Notes: <?= e($item['notes']) ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
