<div class="archive-card p-4 mb-4">
    <div class="d-flex justify-content-between gap-3">
        <div>
            <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Seller profile</p>
            <h1 class="h2 mb-2"><?= e($seller['username']) ?></h1>
            <div class="mb-2">
                <?php if (!empty($seller['email_verified_at'])): ?><span class="badge text-bg-success">Email verified</span><?php endif; ?>
            </div>
            <div class="text-muted"><?= e($seller['city']) ?>, <?= e($seller['province']) ?> · Member since <?= e(date('M Y', strtotime($seller['created_at']))) ?></div>
        </div>
        <?php if (is_logged_in() && (int) current_user()['id'] !== (int) $seller['id']): ?>
            <a class="btn btn-sm btn-outline-danger align-self-start" href="<?= e(url('/reports/create?type=user&id=' . (int) $seller['id'])) ?>">Report seller</a>
        <?php endif; ?>
    </div>
    <?php if (!empty($seller['seller_bio'])): ?><p class="mt-3 mb-0"><?= nl2br(e($seller['seller_bio'])) ?></p><?php endif; ?>
    <div class="row g-3 mt-2">
        <div class="col-sm-6 col-lg-3"><div class="border rounded p-3 h-100"><strong><?= e(number_format((float) $metrics['average_rating'], 1)) ?> / 5</strong><div class="small text-muted"><?= e((string) $metrics['review_count']) ?> verified reviews</div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="border rounded p-3 h-100"><strong><?= e(number_format((float) $metrics['completion_rate'], 1)) ?>%</strong><div class="small text-muted">Order completion rate</div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="border rounded p-3 h-100"><strong><?= e((string) $metrics['completed_sales']) ?></strong><div class="small text-muted">Completed sales</div></div></div>
        <div class="col-sm-6 col-lg-3"><div class="border rounded p-3 h-100"><strong><?= e((string) $seller['active_listing_count']) ?></strong><div class="small text-muted">Active listings</div></div></div>
    </div>
</div>

<h2 class="h4 mb-3">Active listings</h2>
<?php if ($listings === []): ?><div class="archive-card p-4 text-muted">This seller has no active listings.</div><?php else: ?>
<div class="row g-3"><?php foreach ($listings as $listing): ?><div class="col-md-6 col-xl-4"><div class="archive-card p-3 h-100"><div class="fw-semibold"><?= e($listing['card_name']) ?></div><div class="small text-muted"><?= e($listing['set_name']) ?> #<?= e($listing['collector_number']) ?></div><div class="market-price my-2">PHP <?= e(number_format((float) $listing['price_php'], 2)) ?></div><a class="btn btn-sm btn-outline-dark" href="<?= e(url('/cards/show?id=' . (int) $listing['card_id'])) ?>">View card</a></div></div><?php endforeach; ?></div>
<?php endif; ?>

<h2 class="h4 mt-5 mb-3">Verified purchase reviews</h2>
<?php if ($reviews === []): ?><div class="archive-card p-4 text-muted">No published reviews yet.</div><?php else: ?>
<div class="row g-3"><?php foreach ($reviews as $review): ?><div class="col-lg-6"><div class="archive-card p-3 h-100"><div class="d-flex justify-content-between gap-3"><div><strong aria-label="<?= e((string) $review['rating']) ?> out of 5 stars"><?= e(str_repeat('★', (int) $review['rating'])) ?><?= e(str_repeat('☆', 5 - (int) $review['rating'])) ?></strong><div class="small text-muted"><?= e($review['reviewer_username']) ?> · Verified purchase · <?= e(date('M j, Y', strtotime($review['created_at']))) ?></div></div><?php if (is_logged_in() && (int) current_user()['id'] !== (int) $review['reviewer_id']): ?><a class="small text-danger" href="<?= e(url('/reports/create?type=review&id=' . (int) $review['id'])) ?>">Report</a><?php endif; ?></div><p class="mt-2 mb-0"><?= nl2br(e($review['body'])) ?></p></div></div><?php endforeach; ?></div>
<?php endif; ?>
