<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Phase 7</p>
        <h1 class="h2 mb-0">Admin Panel</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-dark" href="<?= e(url('/admin/users')) ?>">Users</a>
        <a class="btn btn-outline-dark" href="<?= e(url('/cards')) ?>">Cards</a>
        <a class="btn btn-outline-dark" href="<?= e(url('/admin/listings')) ?>">Listings</a>
        <a class="btn btn-outline-dark" href="<?= e(url('/admin/orders')) ?>">Orders</a>
        <a class="btn btn-outline-dark" href="<?= e(url('/admin/prices')) ?>">Prices</a>
        <a class="btn btn-outline-dark" href="<?= e(url('/admin/wallets')) ?>">Wallets</a>
        <a class="btn btn-outline-danger" href="<?= e(url('/admin/reports')) ?>">Reports (<?= e((string) $counts['openReports']) ?>)</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl">
        <div class="archive-card p-4"><div class="text-muted small text-uppercase fw-semibold">Users</div><div class="display-6 fw-bold"><?= e((string) $counts['users']) ?></div></div>
    </div>
    <div class="col-md-6 col-xl">
        <div class="archive-card p-4"><div class="text-muted small text-uppercase fw-semibold">Cards</div><div class="display-6 fw-bold"><?= e((string) $counts['cards']) ?></div></div>
    </div>
    <div class="col-md-6 col-xl">
        <div class="archive-card p-4"><div class="text-muted small text-uppercase fw-semibold">Listings</div><div class="display-6 fw-bold"><?= e((string) $counts['listings']) ?></div></div>
    </div>
    <div class="col-md-6 col-xl">
        <div class="archive-card p-4"><div class="text-muted small text-uppercase fw-semibold">Price Entries</div><div class="display-6 fw-bold"><?= e((string) $counts['prices']) ?></div></div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="archive-card p-4">
            <div class="d-flex justify-content-between gap-2 mb-3">
                <h2 class="h4 mb-0">Recent Listings</h2>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/listings')) ?>">Manage</a>
            </div>
            <?php if ($recentListings === []): ?>
                <p class="text-muted mb-0">No listings yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Card</th><th>Seller</th><th>Price</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentListings as $listing): ?>
                                <tr>
                                    <td><?= e($listing['card_name']) ?></td>
                                    <td><?= e($listing['username']) ?></td>
                                    <td>PHP <?= e(number_format((float) $listing['price_php'], 2)) ?></td>
                                    <td><span class="badge text-bg-light"><?= e(ucfirst($listing['status'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="archive-card p-4">
            <div class="d-flex justify-content-between gap-2 mb-3">
                <h2 class="h4 mb-0">Recent Prices</h2>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/prices')) ?>">Manage</a>
            </div>
            <?php if ($recentPrices === []): ?>
                <p class="text-muted mb-0">No price entries yet.</p>
            <?php else: ?>
                <?php foreach ($recentPrices as $price): ?>
                    <div class="admin-mini-row">
                        <div class="fw-semibold"><?= e($price['card_name']) ?></div>
                        <div class="small text-muted"><?= e($price['source_name']) ?> | PHP <?= e(number_format((float) $price['converted_php_price'], 2)) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
