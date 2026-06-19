<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="archive-card p-4 text-center">
            <h1 class="h3">Verify your email</h1>
            <p class="text-muted">We sent a verification link to <strong><?= e(current_user()['email']) ?></strong>. Verify it before buying or selling.</p>
            <?php if ($link = flash('verification_link')): ?>
                <div class="alert alert-warning text-start"><strong>Development link:</strong><br><a href="<?= e($link) ?>"><?= e($link) ?></a></div>
            <?php endif; ?>
            <form method="post" action="<?= e(url('/verify-email/resend')) ?>">
                <button class="btn btn-archive" type="submit">Resend verification email</button>
            </form>
        </div>
    </div>
</div>
