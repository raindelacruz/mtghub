<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="archive-card p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                <div>
                    <p class="text-uppercase fw-semibold archive-kicker-dark mb-1"><?= e($card['set_name']) ?> #<?= e($card['collector_number']) ?></p>
                    <h1 class="h3 mb-0"><?= e($title) ?></h1>
                    <div class="text-muted"><?= e($card['card_name']) ?> | <?= e($card['type_line']) ?></div>
                </div>
                <a class="btn btn-sm btn-outline-secondary align-self-md-start" href="<?= e(url('/collection')) ?>">Back</a>
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
                    <div class="col-md-4">
                        <label class="form-label" for="quantity">Quantity</label>
                        <input class="form-control" id="quantity" name="quantity" type="number" min="1" value="<?= e((string) $item['quantity']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="card_condition">Condition</label>
                        <select class="form-select" id="card_condition" name="card_condition" required>
                            <?php foreach (['near_mint', 'lightly_played', 'moderately_played', 'heavily_played', 'damaged'] as $condition): ?>
                                <option value="<?= e($condition) ?>" <?= $item['card_condition'] === $condition ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $condition))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="language">Language</label>
                        <input class="form-control" id="language" name="language" value="<?= e($item['language']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="acquisition_price">Acquisition price PHP</label>
                        <input class="form-control" id="acquisition_price" name="acquisition_price" type="number" min="0" step="0.01" value="<?= e((string) $item['acquisition_price']) ?>">
                    </div>
                    <div class="col-md-8 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" id="is_foil" name="is_foil" type="checkbox" value="1" <?= !empty($item['is_foil']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_foil">Foil copy</label>
                        </div>
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
