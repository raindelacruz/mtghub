<section class="archive-hero p-4 p-md-5 rounded-3 mb-4">
    <div class="row align-items-center g-4">
        <div class="col-lg-8">
            <p class="text-uppercase fw-semibold archive-kicker mb-2">MTGHub PH MVP</p>
            <h1 class="display-6 fw-bold mb-3">A local-first Magic hub for cards, collections, prices, listings, and wanted lists.</h1>
            <p class="lead mb-4">Phase 8 polishes the experience with clearer dashboards, profile editing, stronger empty states, and mobile-friendly controls.</p>
            <?php if (is_logged_in()): ?>
                <div class="archive-panel p-3">
                    <div class="fw-semibold">Welcome back, <?= e(current_user()['username']) ?>.</div>
                    <div class="text-muted">Role: <?= e(current_user()['role']) ?> | <?= e(current_user()['city']) ?>, <?= e(current_user()['province']) ?></div>
                </div>
            <?php else: ?>
                <a class="btn btn-archive me-2" href="<?= e(url('/register')) ?>">Create account</a>
                <a class="btn btn-outline-light" href="<?= e(url('/cards')) ?>">Browse cards</a>
            <?php endif; ?>
        </div>
        <div class="col-lg-4">
            <div class="archive-panel p-4">
                <h2 class="h5 mb-3">Quick Start</h2>
                <div class="d-grid gap-2">
                    <a class="btn btn-archive" href="<?= e(url('/cards')) ?>">Browse card database</a>
                    <a class="btn btn-outline-dark" href="<?= e(url('/listings')) ?>">View marketplace</a>
                    <?php if (is_admin()): ?>
                        <a class="btn btn-outline-dark" href="<?= e(url('/admin')) ?>">Open admin panel</a>
                        <a class="btn btn-outline-dark" href="<?= e(url('/admin/listings')) ?>">Review listings</a>
                    <?php elseif (is_logged_in()): ?>
                        <a class="btn btn-outline-dark" href="<?= e(url('/collection')) ?>">Open collection</a>
                        <a class="btn btn-outline-dark" href="<?= e(url('/buylist')) ?>">Check wanted list matches</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="row g-4">
    <div class="col-md-6 col-xl-3">
        <a class="feature-tile" href="<?= e(url('/cards')) ?>">
            <span class="feature-number">01</span>
            <h2 class="h5">Card Database</h2>
            <p>Search cards, view details, and let admins maintain local records.</p>
        </a>
    </div>
    <div class="col-md-6 col-xl-3">
        <a class="feature-tile" href="<?= e(is_admin() ? url('/admin') : url('/collection')) ?>">
            <span class="feature-number">02</span>
            <?php if (is_admin()): ?>
                <h2 class="h5">Admin Dashboard</h2>
                <p>Review platform users, listings, orders, prices, and store credit.</p>
            <?php else: ?>
                <h2 class="h5">Collection</h2>
                <p>Track quantities, condition, foil status, acquisition cost, and estimates.</p>
            <?php endif; ?>
        </a>
    </div>
    <div class="col-md-6 col-xl-3">
        <a class="feature-tile" href="<?= e(url('/prices')) ?>">
            <span class="feature-number">03</span>
            <h2 class="h5">Prices</h2>
            <p>Review manual Philippine price entries and latest PHP values.</p>
        </a>
    </div>
    <div class="col-md-6 col-xl-3">
        <a class="feature-tile" href="<?= e(is_admin() ? url('/admin/mtghub-buylist') : url('/buylist')) ?>">
            <span class="feature-number">04</span>
            <?php if (is_admin()): ?>
                <h2 class="h5">MTGHub Buylist</h2>
                <p>Maintain platform buylist entries and review submitted sell orders.</p>
            <?php else: ?>
                <h2 class="h5">Wanted List Matches</h2>
                <p>List cards you want to buy and let matching sellers send offers.</p>
            <?php endif; ?>
        </a>
    </div>
</section>
