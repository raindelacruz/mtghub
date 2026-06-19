<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="archive-card p-4">
            <h1 class="h3 mb-3">Choose a new password</h1>
            <?php if ($errors !== []): ?>
                <div class="alert alert-danger"><ul class="mb-0">
                    <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                </ul></div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/reset-password?token=' . urlencode($token))) ?>">
                <div class="mb-3">
                    <label class="form-label" for="password">New password</label>
                    <input class="form-control" id="password" name="password" type="password" minlength="8" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirm">Confirm new password</label>
                    <input class="form-control" id="password_confirm" name="password_confirm" type="password" minlength="8" required>
                </div>
                <button class="btn btn-archive" type="submit">Reset password</button>
            </form>
        </div>
    </div>
</div>
