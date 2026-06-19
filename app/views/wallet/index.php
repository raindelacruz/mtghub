<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Store Credit</p>
        <h1 class="h2 mb-0">Wallet</h1>
    </div>
    <div class="d-flex gap-2">
        <?php if (is_admin()): ?>
            <a class="btn btn-outline-dark" href="<?= e(url('/admin/wallets')) ?>">Manage Store Credit</a>
        <?php endif; ?>
        <a class="btn btn-archive" href="<?= e(url('/listings')) ?>">Shop marketplace</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="archive-card p-4">
            <div class="text-muted small text-uppercase fw-semibold">Store Credit Balance</div>
            <div class="display-6 fw-bold">₱<?= e(number_format((float) $wallet['store_credit_balance'], 2)) ?></div>
            <p class="text-muted mb-0">Store Credit may be used for MTGHub purchases but cannot be withdrawn as cash.</p>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="archive-card p-4">
            <h2 class="h4 mb-3">Transaction History</h2>

            <?php if ($transactions === []): ?>
                <p class="text-muted mb-0">No store credit activity yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Balance After</th>
                                <th>Reference</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?= e($transaction['created_at']) ?></td>
                                    <td><?= e(str_replace('_', ' ', $transaction['transaction_type'])) ?></td>
                                    <td>₱<?= e(number_format((float) $transaction['amount'], 2)) ?></td>
                                    <td>₱<?= e(number_format((float) $transaction['balance_after'], 2)) ?></td>
                                    <td>
                                        <?php if (!empty($transaction['reference_type'])): ?>
                                            <?= e($transaction['reference_type']) ?><?= $transaction['reference_id'] ? ' #' . e((string) $transaction['reference_id']) : '' ?>
                                        <?php else: ?>
                                            <span class="text-muted">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($transaction['notes'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
