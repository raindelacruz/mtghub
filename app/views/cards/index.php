<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Phase 2</p>
        <h1 class="h2 mb-0">Card Database</h1>
    </div>
    <?php if (is_admin()): ?>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-dark" href="<?= e(url('/cards/import')) ?>">Import from Scryfall</a>
            <a class="btn btn-archive" href="<?= e(url('/cards/create')) ?>">Add card</a>
        </div>
    <?php endif; ?>
</div>

<form class="archive-card p-3 mb-4" method="get" action="<?= e(url('/cards')) ?>">
    <input type="hidden" name="url" value="cards">
    <div class="row g-3">
        <div class="col-lg-4">
            <label class="form-label" for="q">Search</label>
            <input class="form-control" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, set, or type">
        </div>
        <div class="col-md-4 col-lg-2">
            <label class="form-label" for="rarity">Rarity</label>
            <select class="form-select" id="rarity" name="rarity">
                <option value="">Any</option>
                <?php foreach ($rarities as $rarity): ?>
                    <option value="<?= e($rarity) ?>" <?= $filters['rarity'] === $rarity ? 'selected' : '' ?>><?= e(ucfirst($rarity)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 col-lg-2">
            <label class="form-label" for="color">Color</label>
            <select class="form-select" id="color" name="color">
                <option value="">Any</option>
                <?php foreach ($colors as $color): ?>
                    <option value="<?= e($color) ?>" <?= $filters['color'] === $color ? 'selected' : '' ?>><?= e($color) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4 col-lg-2">
            <label class="form-label" for="set_name">Set</label>
            <select class="form-select" id="set_name" name="set_name">
                <option value="">Any</option>
                <?php foreach ($sets as $set): ?>
                    <option value="<?= e($set) ?>" <?= $filters['set_name'] === $set ? 'selected' : '' ?>><?= e($set) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-lg-2 d-flex align-items-end gap-2">
            <button class="btn btn-archive w-100" type="submit">Filter</button>
            <a class="btn btn-outline-secondary" href="<?= e(url('/cards')) ?>">Reset</a>
        </div>
    </div>
</form>

<?php if ($cards === []): ?>
    <div class="archive-card p-4 text-center">
        <h2 class="h5">No cards found yet.</h2>
        <p class="text-muted mb-0">An admin can add cards using the Add card button.</p>
    </div>
<?php else: ?>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
        <div class="text-muted small">
            Showing <?= e((string) ((($pagination['page'] - 1) * $pagination['perPage']) + 1)) ?>
            - <?= e((string) min($pagination['page'] * $pagination['perPage'], $pagination['total'])) ?>
            of <?= e((string) $pagination['total']) ?> cards
        </div>
    </div>

    <div class="cards-grid">
        <?php foreach ($cards as $card): ?>
            <div class="archive-card card-card card-listing-card">
                    <div class="card-image-wrap">
                        <?php if (!empty($card['image_url'])): ?>
                            <img src="<?= e($card['image_url']) ?>" alt="<?= e($card['card_name']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="card-image-placeholder">MTGHub PH</div>
                        <?php endif; ?>
                    </div>
                    <div class="card-listing-body">
                        <div class="d-flex justify-content-between gap-2 align-items-start">
                            <h2 class="card-listing-title mb-1"><?= e($card['card_name']) ?></h2>
                            <span class="badge text-bg-light"><?= e(ucfirst($card['rarity'])) ?></span>
                        </div>
                        <div class="text-muted small mb-2"><?= e($card['set_name']) ?> #<?= e($card['collector_number']) ?></div>
                        <div class="card-listing-type mb-3"><?= e($card['type_line']) ?></div>
                        <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/cards/show?id=' . (int) $card['id'])) ?>">View card</a>
                        <?php if (is_admin()): ?>
                            <a class="btn btn-sm btn-link" href="<?= e(url('/cards/edit?id=' . (int) $card['id'])) ?>">Edit</a>
                        <?php endif; ?>
                    </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pagination['totalPages'] > 1): ?>
        <?php
            $pageWindowStart = max(1, $pagination['page'] - 2);
            $pageWindowEnd = min($pagination['totalPages'], $pagination['page'] + 2);
            $pageUrl = static function (int $pageNumber) use ($filters): string {
                $params = ['url' => 'cards', 'page' => $pageNumber];
                foreach ($filters as $key => $value) {
                    if ($value !== '') {
                        $params[$key] = $value;
                    }
                }

                return url('/cards') . '?' . http_build_query($params);
            };
        ?>
        <nav class="mt-4" aria-label="Card pagination">
            <ul class="pagination justify-content-center flex-wrap">
                <li class="page-item <?= $pagination['page'] <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e($pageUrl(max(1, $pagination['page'] - 1))) ?>">Previous</a>
                </li>
                <?php for ($pageNumber = $pageWindowStart; $pageNumber <= $pageWindowEnd; $pageNumber++): ?>
                    <li class="page-item <?= $pageNumber === $pagination['page'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= e($pageUrl($pageNumber)) ?>"><?= e((string) $pageNumber) ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $pagination['page'] >= $pagination['totalPages'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= e($pageUrl(min($pagination['totalPages'], $pagination['page'] + 1))) ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>
