<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="archive-card p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                <div>
                    <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Account</p>
                    <h1 class="h3 mb-0">My Profile</h1>
                    <div class="text-muted"><?= e($user['email']) ?></div>
                </div>
                <a class="btn btn-sm btn-outline-secondary align-self-md-start" href="<?= e(url('/')) ?>">Dashboard</a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/profile/update')) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control" id="username" name="username" value="<?= e($user['username']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="seller_bio">Seller bio</label>
                        <textarea class="form-control" id="seller_bio" name="seller_bio" maxlength="500" rows="3"><?= e($user['seller_bio'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <input class="form-control" value="<?= e($user['role']) ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="city">City</label>
                        <input class="form-control" id="city" name="city" value="<?= e($user['city']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="province">Province</label>
                        <input class="form-control" id="province" name="province" value="<?= e($user['province']) ?>" required>
                    </div>
                </div>

                <button class="btn btn-archive mt-4" type="submit">Save profile</button>
            </form>
            <hr>
            <a class="btn btn-outline-dark" href="<?= e(url('/change-password')) ?>">Change password</a>
            <a class="btn btn-outline-dark" href="<?= e(url('/notifications/preferences')) ?>">Notification preferences</a>
            <a class="btn btn-outline-danger" href="<?= e(url('/account/deletion')) ?>">Delete account</a>
        </div>
    </div>
</div>
