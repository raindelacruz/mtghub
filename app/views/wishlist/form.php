<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="archive-card p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                <div>
                    <p class="text-uppercase fw-semibold archive-kicker-dark mb-1"><?= e($card['set_name']) ?> #<?= e($card['collector_number']) ?></p>
                    <h1 class="h3 mb-0"><?= e($title) ?></h1>
                    <div class="text-muted"><?= e($card['card_name']) ?> | <?= e($card['type_line']) ?></div>
                </div>
                <a class="btn btn-sm btn-outline-secondary align-self-md-start" href="<?= e(url('/buylist')) ?>">Back</a>
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

            <form method="post" action="<?= e($action) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="desired_quantity">Buying quantity</label>
                        <input class="form-control" id="desired_quantity" name="desired_quantity" type="number" min="1" value="<?= e((string) $item['desired_quantity']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="max_price_php">Max buy price PHP</label>
                        <input class="form-control" id="max_price_php" name="max_price_php" type="number" min="0" step="0.01" value="<?= e((string) ($item['max_price_php'] ?? '')) ?>" placeholder="Leave blank for any price">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" maxlength="1000"><?= e($item['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <button class="btn btn-archive mt-4" type="submit"><?= e($buttonText) ?></button>
            </form>
        </div>
    </div>
</div>
