<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="archive-card p-4">
            <h1 class="h3 mb-3">Change password</h1>
            <?php if ($errors !== []): ?>
                <div class="alert alert-danger"><ul class="mb-0">
                    <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                </ul></div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/change-password')) ?>">
                <div class="mb-3">
                    <label class="form-label" for="current_password">Current password</label>
                    <input class="form-control" id="current_password" name="current_password" type="password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">New password</label>
                    <input class="form-control" id="password" name="password" type="password" minlength="8" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirm">Confirm new password</label>
                    <input class="form-control" id="password_confirm" name="password_confirm" type="password" minlength="8" required>
                </div>
                <button class="btn btn-archive" type="submit">Update password</button>
                <a class="btn btn-link" href="<?= e(url('/profile')) ?>">Back to profile</a>
            </form>
        </div>
    </div>
</div>
