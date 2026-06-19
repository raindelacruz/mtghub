<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="archive-card p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                <div>
                    <p class="text-uppercase fw-semibold archive-kicker-dark mb-1"><?= e($card['set_name']) ?> #<?= e($card['collector_number']) ?></p>
                    <h1 class="h3 mb-0"><?= e($title) ?></h1>
                    <div class="text-muted"><?= e($card['card_name']) ?></div>
                </div>
                <a class="btn btn-sm btn-outline-secondary align-self-md-start" href="<?= e(url('/cards/show?id=' . (int) $card['id'])) ?>">Back</a>
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
                        <label class="form-label" for="source_name">Source name</label>
                        <input class="form-control" id="source_name" name="source_name" value="<?= e($price['source_name']) ?>" placeholder="Local shop, FB group, marketplace" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="currency">Currency</label>
                        <input class="form-control" id="currency" name="currency" maxlength="3" value="<?= e($price['currency']) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="date_captured">Date captured</label>
                        <input class="form-control" id="date_captured" name="date_captured" type="date" value="<?= e($price['date_captured']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="price">Original price</label>
                        <input class="form-control" id="price" name="price" type="number" min="0" step="0.01" value="<?= e((string) $price['price']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="converted_php_price">Converted PHP price</label>
                        <input class="form-control" id="converted_php_price" name="converted_php_price" type="number" min="0" step="0.01" value="<?= e((string) $price['converted_php_price']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" maxlength="1000"><?= e($price['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <button class="btn btn-archive mt-4" type="submit"><?= e($buttonText) ?></button>
            </form>
        </div>
    </div>
</div>
