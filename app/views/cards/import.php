<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Scryfall</p>
        <h1 class="h2 mb-0">Import Card Data</h1>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(url('/cards')) ?>">Back to cards</a>
        <a class="btn btn-archive" href="<?= e(url('/cards/create')) ?>">Manual add</a>
    </div>
</div>

<form class="archive-card p-3 mb-4" method="get" action="<?= e(url('/cards/import')) ?>">
    <input type="hidden" name="url" value="cards/import">
    <div class="row g-3 align-items-end">
        <div class="col-md-9">
            <label class="form-label" for="q">Search Scryfall</label>
            <input class="form-control" id="q" name="q" value="<?= e($query) ?>" placeholder="Card name, set code, oracle text, or Scryfall syntax">
        </div>
        <div class="col-md-3">
            <button class="btn btn-archive w-100" type="submit">Search</button>
        </div>
    </div>
</form>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="archive-card p-4 mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
        <div>
            <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Bulk Sync</p>
            <h2 class="h5">Make the full Scryfall card database available locally.</h2>
            <p class="text-muted mb-0">This downloads Scryfall's default card bulk data, streams it into MTGHub, and updates existing cards by Scryfall ID.</p>
        </div>
        <form class="d-flex flex-column flex-sm-row gap-2 align-self-lg-center" method="post" action="<?= e(url('/cards/sync-scryfall')) ?>" onsubmit="return confirm('Start a full Scryfall sync? This may take several minutes.');">
            <input class="form-control" type="number" name="limit" min="1" placeholder="Optional test limit">
            <button class="btn btn-archive" type="submit">Sync all cards</button>
        </form>
    </div>
</div>

<?php if ($query === ''): ?>
    <div class="archive-card p-4 text-center">
        <h2 class="h5">Search the official Scryfall card database.</h2>
        <p class="text-muted mb-0">Imported cards are saved locally for collections, listings, buylists, and price tracking.</p>
    </div>
<?php elseif ($results === [] && !$error): ?>
    <div class="archive-card p-4 text-center">
        <h2 class="h5">No Scryfall matches found.</h2>
        <p class="text-muted mb-0">Try a broader card name or Scryfall search syntax.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($results as $result): ?>
            <?php $card = $scryfall->toCardData($result); ?>
            <div class="col-md-6 col-xl-4">
                <div class="archive-card card-card h-100">
                    <div class="card-image-wrap">
                        <?php if (!empty($card['image_url'])): ?>
                            <img src="<?= e($card['image_url']) ?>" alt="<?= e($card['card_name']) ?>">
                        <?php else: ?>
                            <div class="card-image-placeholder">No Image</div>
                        <?php endif; ?>
                    </div>
                    <div class="p-3">
                        <div class="d-flex justify-content-between gap-2">
                            <h2 class="h5 mb-1"><?= e($card['card_name']) ?></h2>
                            <span class="badge text-bg-light"><?= e(ucfirst($card['rarity'])) ?></span>
                        </div>
                        <div class="text-muted small mb-2"><?= e($card['set_name']) ?> #<?= e($card['collector_number']) ?></div>
                        <div class="mb-3"><?= e($card['type_line']) ?></div>
                        <form method="post" action="<?= e(url('/cards/import')) ?>">
                            <input type="hidden" name="scryfall_id" value="<?= e($card['scryfall_id']) ?>">
                            <button class="btn btn-sm btn-archive" type="submit">Import card</button>
                            <?php if (!empty($result['scryfall_uri'])): ?>
                                <a class="btn btn-sm btn-link" href="<?= e($result['scryfall_uri']) ?>" target="_blank" rel="noopener">View on Scryfall</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
