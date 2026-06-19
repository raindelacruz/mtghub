<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Admin</p>
        <h1 class="h2 mb-0">Store Credit Wallets</h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin')) ?>">Dashboard</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="archive-card p-4">
            <form class="mb-3" method="get" action="<?= e(url('/admin/wallets')) ?>">
                <label class="form-label" for="q">Search users</label>
                <div class="input-group">
                    <input class="form-control" id="q" name="q" value="<?= e($query) ?>" placeholder="Name or email">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>User</th><th class="text-end">Balance</th></tr></thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        <a class="link-dark" href="<?= e(url('/admin/wallets?user_id=' . (int) $user['id'])) ?>"><?= e($user['username']) ?></a>
                                    </div>
                                    <div class="text-muted small"><?= e($user['email']) ?></div>
                                    <a class="btn btn-sm btn-outline-secondary mt-2" href="<?= e(url('/admin/wallets?user_id=' . (int) $user['id'])) ?>">Manage balance</a>
                                </td>
                                <td class="text-end">₱<?= e(number_format((float) $user['store_credit_balance'], 2)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="archive-card p-4">
            <?php if ($selectedUser === null): ?>
                <h2 class="h4">Select a user</h2>
                <p class="text-muted mb-0">Choose a user to credit, debit, or review their store credit history.</p>
            <?php else: ?>
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                    <div>
                        <h2 class="h4 mb-1"><?= e($selectedUser['username']) ?></h2>
                        <div class="text-muted"><?= e($selectedUser['email']) ?></div>
                    </div>
                    <div class="text-md-end">
                        <div class="text-muted small text-uppercase fw-semibold">Store Credit Balance</div>
                        <div class="fs-3 fw-bold">₱<?= e(number_format((float) $selectedWallet['store_credit_balance'], 2)) ?></div>
                    </div>
                </div>

                <form class="row g-3 mb-4" method="post" action="<?= e(url('/admin/wallets/adjust')) ?>">
                    <input type="hidden" name="user_id" value="<?= e((string) $selectedUser['id']) ?>">
                    <div class="col-md-4">
                        <label class="form-label" for="direction">Direction</label>
                        <select class="form-select" id="direction" name="direction" required>
                            <option value="credit">Credit</option>
                            <option value="debit">Debit</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="amount">Amount</label>
                        <input class="form-control" id="amount" name="amount" type="number" min="0.01" step="0.01" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-archive w-100" type="submit">Save adjustment</button>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="1000" required></textarea>
                    </div>
                </form>

                <h3 class="h5 mb-3">Transaction History</h3>
                <?php if ($transactions === []): ?>
                    <p class="text-muted mb-0">No store credit activity yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Balance After</th><th>Notes</th></tr></thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td><?= e($transaction['created_at']) ?></td>
                                        <td><?= e(str_replace('_', ' ', $transaction['transaction_type'])) ?></td>
                                        <td>₱<?= e(number_format((float) $transaction['amount'], 2)) ?></td>
                                        <td>₱<?= e(number_format((float) $transaction['balance_after'], 2)) ?></td>
                                        <td><?= e($transaction['notes'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
