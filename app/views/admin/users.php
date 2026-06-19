<div class="d-flex justify-content-between align-items-center mb-4">
    <div><p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Admin</p><h1 class="h2 mb-0">Users and verification</h1></div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin')) ?>">Dashboard</a>
</div>

<div class="row g-3">
<?php foreach ($users as $user): ?>
    <div class="col-12"><div class="archive-card p-3">
        <div class="row g-3 align-items-start">
            <div class="col-lg-3">
                <div class="fw-semibold"><a href="<?= e(url('/sellers/show?id=' . (int) $user['id'])) ?>"><?= e($user['username']) ?></a></div>
                <div class="text-muted small"><?= e($user['email']) ?></div>
                <div class="small"><?= e($user['city']) ?>, <?= e($user['province']) ?></div>
                <div class="mt-2"><span class="badge text-bg-<?= $user['account_status'] === 'active' ? 'success' : ($user['account_status'] === 'pending' ? 'warning' : 'danger') ?>"><?= e($user['account_status']) ?></span>
                    <span class="badge text-bg-light"><?= e($user['role']) ?></span></div>
                <div class="small mt-2">Email: <?= !empty($user['email_verified_at']) ? 'Verified' : 'Pending' ?></div>
            </div>
            <div class="col-lg-3">
                <form method="post" action="<?= e(url('/admin/users/role?id=' . (int) $user['id'])) ?>">
                    <label class="form-label small">Role</label><div class="d-flex gap-2"><select class="form-select form-select-sm" name="role"><option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option><option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option></select><button class="btn btn-sm btn-outline-dark">Save</button></div>
                </form>
            </div>
            <div class="col-lg-6">
                <form method="post" action="<?= e(url('/admin/users/status?id=' . (int) $user['id'])) ?>">
                    <div class="row g-2"><div class="col-md-4"><label class="form-label small">Account status</label><select class="form-select form-select-sm" name="account_status"><?php foreach (['pending','active','suspended','banned'] as $status): ?><option value="<?= e($status) ?>" <?= $user['account_status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-8"><label class="form-label small">Suspension/ban reason</label><input class="form-control form-control-sm" name="suspension_reason" maxlength="500" value="<?= e($user['suspension_reason'] ?? '') ?>"></div>
                    <div class="col-12"><label class="form-label small">Internal moderator notes</label><textarea class="form-control form-control-sm" name="moderation_notes" maxlength="2000" rows="2"><?= e($user['moderation_notes'] ?? '') ?></textarea></div></div>
                    <button class="btn btn-sm btn-archive mt-2" type="submit">Update account</button>
                </form>
            </div>
        </div>
    </div></div>
<?php endforeach; ?>
</div>
