<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="archive-card p-4">
            <h1 class="h3 mb-3">Log in</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/login')) ?>">
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" value="<?= e($old['email'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password" required>
                </div>

                <button class="btn btn-archive" type="submit">Login</button>
                <a class="btn btn-link" href="<?= e(url('/register')) ?>">Create account</a>
                <a class="btn btn-link" href="<?= e(url('/forgot-password')) ?>">Forgot password?</a>
            </form>
        </div>
    </div>
</div>
