<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Admin</p>
        <h1 class="h2 mb-0">Manage Price Entries</h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin')) ?>">Dashboard</a>
</div>

<div class="archive-card table-responsive">
    <table class="table align-middle mb-0">
        <thead>
            <tr><th>Card</th><th>Source</th><th>Original</th><th>PHP</th><th>Date</th><th class="text-end">Action</th></tr>
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
                    <td>PHP <?= e(number_format((float) $price['converted_php_price'], 2)) ?></td>
                    <td><?= e($price['date_captured']) ?></td>
                    <td class="text-end">
                        <form method="post" action="<?= e(url('/admin/prices/delete?id=' . (int) $price['id'])) ?>" onsubmit="return confirm('Delete this price entry?');">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
