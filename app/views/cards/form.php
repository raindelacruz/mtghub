<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="archive-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h3 mb-0"><?= e($title) ?></h1>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/cards')) ?>">Back</a>
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
                    <div class="col-md-8">
                        <label class="form-label" for="card_name">Card name</label>
                        <input class="form-control" id="card_name" name="card_name" value="<?= e($card['card_name']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="collector_number">Collector number</label>
                        <input class="form-control" id="collector_number" name="collector_number" value="<?= e($card['collector_number']) ?>">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="set_name">Set name</label>
                        <input class="form-control" id="set_name" name="set_name" value="<?= e($card['set_name']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="rarity">Rarity</label>
                        <select class="form-select" id="rarity" name="rarity" required>
                            <?php foreach (['common', 'uncommon', 'rare', 'mythic'] as $rarity): ?>
                                <option value="<?= e($rarity) ?>" <?= $card['rarity'] === $rarity ? 'selected' : '' ?>><?= e(ucfirst($rarity)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="color">Color</label>
                        <input class="form-control" id="color" name="color" value="<?= e($card['color']) ?>" placeholder="White, Blue, Colorless">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label" for="type_line">Type</label>
                        <input class="form-control" id="type_line" name="type_line" value="<?= e($card['type_line']) ?>" placeholder="Legendary Creature - Human Wizard" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="image_url">Image URL</label>
                        <input class="form-control" id="image_url" name="image_url" type="url" value="<?= e($card['image_url'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="scryfall_id">Scryfall ID</label>
                        <input class="form-control" id="scryfall_id" name="scryfall_id" value="<?= e($card['scryfall_id'] ?? '') ?>">
                    </div>
                </div>

                <button class="btn btn-archive mt-4" type="submit"><?= e($buttonText) ?></button>
            </form>
        </div>
    </div>
</div>
