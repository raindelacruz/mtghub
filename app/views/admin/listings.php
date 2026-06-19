<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Admin</p>
        <h1 class="h2 mb-0">Manage Listings</h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin')) ?>">Dashboard</a>
</div>

<div class="archive-card table-responsive">
    <table class="table align-middle mb-0">
        <thead>
            <tr><th>Card</th><th>Seller</th><th>Price</th><th>Location</th><th>Status</th><th class="text-end">Action</th></tr>
        </thead>
        <tbody>
            <?php foreach ($listings as $listing): ?>
                <tr>
                    <td>
                        <a class="fw-semibold text-dark" href="<?= e(url('/cards/show?id=' . (int) $listing['card_id'])) ?>"><?= e($listing['card_name']) ?></a>
                        <div class="text-muted small"><?= e($listing['set_name']) ?> #<?= e($listing['collector_number']) ?></div>
                    </td>
                    <td><?= e($listing['username']) ?></td>
                    <td>PHP <?= e(number_format((float) $listing['price_php'], 2)) ?></td>
                    <td><?= e($listing['seller_location']) ?></td>
                    <td><span class="badge text-bg-light"><?= e(ucfirst($listing['status'])) ?></span></td>
                    <td class="text-end">
                        <form class="d-inline-flex gap-2" method="post" action="<?= e(url('/admin/listings/status?id=' . (int) $listing['id'])) ?>">
                            <select class="form-select form-select-sm" name="status">
                                <?php foreach (['active', 'reserved', 'sold', 'cancelled'] as $status): ?>
                                    <option value="<?= e($status) ?>" <?= $listing['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-sm btn-archive" type="submit">Save</button>
                        </form>
                    </td>
                </tr>
                <?php if (!empty($listing['notes'])): ?>
                    <tr class="collection-notes-row"><td colspan="6" class="text-muted small">Notes: <?= e($listing['notes']) ?></td></tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
