<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="archive-card p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                <div>
                    <p class="text-uppercase fw-semibold archive-kicker-dark mb-1"><?= e($card['set_name']) ?> #<?= e($card['collector_number']) ?></p>
                    <h1 class="h3 mb-0"><?= e($title) ?></h1>
                    <div class="text-muted"><?= e($card['card_name']) ?> | <?= e($card['type_line']) ?></div>
                </div>
                <a class="btn btn-sm btn-outline-secondary align-self-md-start" href="<?= e(url('/listings/mine')) ?>">Back</a>
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
                        <input class="form-control" id="quantity" name="quantity" type="number" min="1" value="<?= e((string) $listing['quantity']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="card_condition">Condition</label>
                        <select class="form-select" id="card_condition" name="card_condition" required>
                            <?php foreach (['near_mint', 'lightly_played', 'moderately_played', 'heavily_played', 'damaged'] as $condition): ?>
                                <option value="<?= e($condition) ?>" <?= $listing['card_condition'] === $condition ? 'selected' : '' ?>><?= e(ucwords(str_replace('_', ' ', $condition))) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <?php foreach (['active', 'reserved', 'sold', 'cancelled'] as $status): ?>
                                <option value="<?= e($status) ?>" <?= $listing['status'] === $status ? 'selected' : '' ?>><?= e(ucfirst($status)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="price_php">Price PHP</label>
                        <input class="form-control" id="price_php" name="price_php" type="number" min="0" step="0.01" value="<?= e((string) $listing['price_php']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="seller_location">Seller location</label>
                        <input class="form-control" id="seller_location" name="seller_location" value="<?= e($listing['seller_location']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="delivery_options">Delivery options</label>
                        <input class="form-control" id="delivery_options" name="delivery_options" value="<?= e($listing['delivery_options']) ?>" placeholder="Meetup, LBC, J&T, Grab/Lalamove" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4" maxlength="1000"><?= e($listing['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <button class="btn btn-archive mt-4" type="submit"><?= e($buttonText) ?></button>
            </form>
        </div>
    </div>
</div>
