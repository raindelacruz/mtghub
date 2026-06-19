<?php
$stylePath = ROOT_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'style.css';
$styleVersion = is_file($stylePath) ? (string) filemtime($stylePath) : '1';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Home') . ' - ' . APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/style.css') . '?v=' . $styleVersion) ?>" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark archive-navbar">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= e(url('/')) ?>">MTGHub PH</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavbar">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('/')) ?>">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('/cards')) ?>">Cards</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('/prices')) ?>">Prices</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('/listings')) ?>">Marketplace</a></li>
                    <?php if (is_logged_in()): ?>
                        <?php $unreadNotifications = unread_notification_count(); ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/notifications')) ?>">Notifications<?php if ($unreadNotifications > 0): ?> <span class="badge text-bg-danger"><?= e((string) min($unreadNotifications, 99)) ?></span><?php endif; ?></a></li>
                        <?php if (is_admin()): ?>
                            <li class="nav-item"><a class="nav-link badge-role" href="<?= e(url('/admin')) ?>">Admin</a></li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Manage
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= e(url('/admin/users')) ?>">Users</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/admin/listings')) ?>">Listings</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/admin/orders')) ?>">Orders</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/admin/prices')) ?>">Prices</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/admin/wallets')) ?>">Wallets</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/admin/wanted-lists')) ?>">Wanted Lists</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/admin/reports')) ?>">Moderation Reports</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/admin/disputes')) ?>">Order Disputes</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/admin/reviews')) ?>">Reviews</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/admin/audit-logs')) ?>">Audit Log</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/admin/operations')) ?>">Operations</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/admin/mtghub-buylist')) ?>">MTGHub Buylist</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/admin/mtghub-buylist/orders')) ?>">Buylist Orders</a></li>
                                </ul>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="<?= e(url('/profile')) ?>">Hi, <?= e(current_user()['username']) ?></a></li>
                        <?php else: ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    My Hub
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= e(url('/collection')) ?>">Collection</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/buylist')) ?>">Wanted List</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/wallet')) ?>">Store Credit</a></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Buy
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= e(url('/cart')) ?>">Cart</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/orders')) ?>">Orders</a></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Sell
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= e(url('/listings/mine')) ?>">My Listings</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/sell-to-mtghub')) ?>">Sell to MTGHub</a></li>
                                    <li><a class="dropdown-item" href="<?= e(url('/my-sell-orders')) ?>">Sell Orders</a></li>
                                </ul>
                            </li>
                            <li class="nav-item"><a class="nav-link" href="<?= e(url('/profile')) ?>">Hi, <?= e(current_user()['username']) ?></a></li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <form method="post" action="<?= e(url('/logout')) ?>">
                                <?= csrf_field() ?>
                                <button class="nav-link border-0 bg-transparent" type="submit">Logout</button>
                            </form>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/login')) ?>">Login</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/register')) ?>">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-5">
        <div class="container">
            <?php if ($message = flash('success')): ?>
                <div class="alert alert-success"><?= e($message) ?></div>
            <?php endif; ?>
            <?php if ($message = flash('error')): ?>
                <div class="alert alert-danger"><?= e($message) ?></div>
            <?php endif; ?>
            <?php if (is_logged_in() && !is_admin() && empty(current_user()['email_verified_at'])): ?>
                <div class="alert alert-warning d-flex justify-content-between align-items-center gap-3">
                    <span>Verify your email to unlock marketplace buying and selling.</span>
                    <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/verify-email')) ?>">Verify email</a>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </main>

    <footer class="archive-footer py-4 mt-auto">
        <div class="container small text-center">
            <div class="mb-2"><a class="link-light me-3" href="<?= e(url('/policies?policy=privacy')) ?>">Privacy</a><a class="link-light me-3" href="<?= e(url('/policies?policy=terms')) ?>">Terms</a><a class="link-light me-3" href="<?= e(url('/policies?policy=marketplace')) ?>">Marketplace rules</a><a class="link-light me-3" href="<?= e(url('/policies?policy=condition-guide')) ?>">Condition guide</a><a class="link-light" href="<?= e(url('/policies?policy=refunds')) ?>">Refunds</a></div>
            MTGHub PH MVP. Magic: The Gathering is owned by Wizards of the Coast. This fan platform is unofficial.
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
