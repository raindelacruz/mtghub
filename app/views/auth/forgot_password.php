<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="archive-card p-4">
            <h1 class="h3 mb-3">Reset your password</h1>
            <p class="text-muted">Enter your account email. If it exists, we will create a time-limited reset link.</p>
            <form method="post" action="<?= e(url('/forgot-password')) ?>">
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" required>
                </div>
                <button class="btn btn-archive" type="submit">Send reset link</button>
                <a class="btn btn-link" href="<?= e(url('/login')) ?>">Back to login</a>
            </form>
        </div>
    </div>
</div>
