<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Phase 5</p>
        <h1 class="h2 mb-0">Marketplace Listings</h1>
    </div>
    <?php if (is_logged_in() && !is_admin()): ?>
        <a class="btn btn-archive" href="<?= e(url('/listings/mine')) ?>">My listings</a>
    <?php endif; ?>
</div>

<form class="archive-card p-3 mb-4" method="get" action="<?= e(url('/listings')) ?>">
    <input type="hidden" name="url" value="listings">
    <div class="row g-3">
        <div class="col-lg-10">
            <label class="form-label" for="q">Search</label>
            <input class="form-control" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Card or set">
        </div>
        <div class="col-lg-2 d-flex align-items-end gap-2">
            <button class="btn btn-archive w-100" type="submit">Search</button>
            <a class="btn btn-outline-secondary" href="<?= e(url('/listings')) ?>">Reset</a>
        </div>
    </div>
</form>

<?php if ($listings === []): ?>
    <div class="archive-card p-4 text-center">
        <h2 class="h5">No listings found.</h2>
        <p class="text-muted mb-0">Try another card or set name.</p>
    </div>
<?php else: ?>
    <div class="text-muted small mb-3">
        Showing <?= e((string) ((($pagination['page'] - 1) * $pagination['perPage']) + 1)) ?>
        - <?= e((string) min($pagination['page'] * $pagination['perPage'], $pagination['total'])) ?>
        of <?= e((string) $pagination['total']) ?> listings
    </div>

    <div class="cards-grid marketplace-grid">
        <?php foreach ($listings as $listing): ?>
            <article class="archive-card card-card card-listing-card d-flex flex-column">
                <a class="card-image-wrap" href="<?= e(url('/cards/show?id=' . (int) $listing['card_id'])) ?>" aria-label="View <?= e($listing['card_name']) ?>">
                    <?php if (!empty($listing['image_url'])): ?>
                        <img src="<?= e($listing['image_url']) ?>" alt="<?= e($listing['card_name']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="card-image-placeholder">MTGHub PH</div>
                    <?php endif; ?>
                </a>
                <div class="card-listing-body d-flex flex-column flex-grow-1">
                    <h2 class="card-listing-title mb-1">
                        <a class="text-dark" href="<?= e(url('/cards/show?id=' . (int) $listing['card_id'])) ?>"><?= e($listing['card_name']) ?></a>
                    </h2>
                    <div class="text-muted small mb-2"><?= e($listing['set_name']) ?> #<?= e($listing['collector_number']) ?></div>
                    <div class="market-price mb-2">PHP <?= e(number_format((float) $listing['price_php'], 2)) ?></div>
                    <div class="small mb-3">
                        <div><strong>Seller:</strong> <a href="<?= e(url('/sellers/show?id=' . (int) $listing['user_id'])) ?>"><?= e($listing['username']) ?></a> <span class="badge text-bg-success">Email verified</span></div>
                        <div><strong>Qty:</strong> <?= e((string) $listing['quantity']) ?></div>
                        <div><strong>Condition:</strong> <?= e(ucwords(str_replace('_', ' ', $listing['card_condition']))) ?></div>
                        <?php if (isset($listing['buylist_demand_count'])): ?>
                            <div><strong>Wanted:</strong> <?= e((string) $listing['buylist_demand_count']) ?> buyer<?= (int) $listing['buylist_demand_count'] === 1 ? '' : 's' ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mt-auto">
                        <?php if (can_trade() && !is_admin() && (int) $listing['quantity'] > 0): ?>
                            <form class="d-flex gap-2" method="post" action="<?= e(url('/cart/add?listing_id=' . (int) $listing['id'])) ?>">
                                <input class="form-control form-control-sm" name="quantity" type="number" min="1" max="<?= e((string) $listing['quantity']) ?>" value="1" aria-label="Quantity" required>
                                <button class="btn btn-sm btn-archive flex-shrink-0" type="submit">Add to cart</button>
                            </form>
                        <?php elseif (!is_logged_in() && (int) $listing['quantity'] > 0): ?>
                            <a class="btn btn-sm btn-archive" href="<?= e(url('/login')) ?>">Log in to buy</a>
                        <?php elseif (is_logged_in() && !is_admin() && !can_trade()): ?>
                            <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/verify-email')) ?>">Verify to buy</a>
                        <?php endif; ?>
                        <?php if (is_logged_in() && (int) current_user()['id'] !== (int) $listing['user_id']): ?>
                            <a class="btn btn-sm btn-link text-danger px-0 mt-2" href="<?= e(url('/reports/create?type=listing&id=' . (int) $listing['id'])) ?>">Report listing</a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if ($pagination['totalPages'] > 1): ?>
        <?php
            $pageWindowStart = max(1, $pagination['page'] - 2);
            $pageWindowEnd = min($pagination['totalPages'], $pagination['page'] + 2);
            $pageUrl = static function (int $pageNumber) use ($filters): string {
                $params = ['url' => 'listings', 'page' => $pageNumber];
                if ($filters['q'] !== '') {
                    $params['q'] = $filters['q'];
                }

                return url('/listings') . '?' . http_build_query($params);
            };
        ?>
        <nav class="mt-4" aria-label="Marketplace pagination">
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
