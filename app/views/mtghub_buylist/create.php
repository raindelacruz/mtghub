<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="archive-card p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                <div>
                    <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">MTGHub Buylist</p>
                    <h1 class="h3 mb-0"><?= e($entry['card_name']) ?></h1>
                    <div class="text-muted"><?= e($entry['set_name'] ?: $entry['card_set_name']) ?> #<?= e($entry['collector_number']) ?></div>
                </div>
                <a class="btn btn-sm btn-outline-secondary align-self-md-start" href="<?= e(url('/sell-to-mtghub')) ?>">Back</a>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4"><strong>Cash:</strong> PHP <?= e(number_format((float) $entry['cash_offer'], 2)) ?></div>
                <div class="col-md-4"><strong>Store credit:</strong> PHP <?= e(number_format((float) $entry['credit_offer'], 2)) ?></div>
                <div class="col-md-4"><strong>Remaining:</strong> <?= e((string) $entry['remaining_quantity']) ?></div>
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

            <form method="post" action="<?= e(url('/sell-to-mtghub/store?buylist_id=' . (int) $entry['id'])) ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="quantity">Quantity</label>
                        <input class="form-control" id="quantity" name="quantity" type="number" min="1" max="<?= e((string) $entry['remaining_quantity']) ?>" value="<?= e((string) $order['quantity']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="declared_condition">Declared condition</label>
                        <select class="form-select" id="declared_condition" name="declared_condition" required>
                            <?php foreach (['near_mint', 'lightly_played', 'moderately_played', 'heavily_played', 'damaged'] as $condition): ?>
                                <option value="<?= e($condition) ?>" <?= $order['declared_condition'] === $condition ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $condition))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="payout_method">Payout method</label>
                        <select class="form-select" id="payout_method" name="payout_method" required>
                            <option value="cash" <?= $order['payout_method'] === 'cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="store_credit" <?= $order['payout_method'] === 'store_credit' ? 'selected' : '' ?>>Store credit</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="remarks">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="4" maxlength="1000"><?= e($order['remarks']) ?></textarea>
                    </div>
                </div>

                <button class="btn btn-archive mt-4" type="submit">Submit sell order</button>
            </form>
        </div>
    </div>
</div>
