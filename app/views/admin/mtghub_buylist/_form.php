<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="archive-card p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                <div>
                    <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Admin</p>
                    <h1 class="h3 mb-0"><?= e($title) ?></h1>
                </div>
                <a class="btn btn-sm btn-outline-secondary align-self-md-start" href="<?= e(url('/admin/mtghub-buylist')) ?>">Back</a>
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
                    <?php if (isset($cardSearchUrl)): ?>
                        <?php
                            $selectedCardLabel = '';
                            if (!empty($selectedCard)) {
                                $selectedCardLabel = trim((string) $selectedCard['card_name']) . ' - ' . trim((string) $selectedCard['set_name']) . ' #' . trim((string) $selectedCard['collector_number']);
                            }
                        ?>
                        <div class="col-12">
                            <label class="form-label" for="card_id">Card</label>
                            <div class="card-picker" data-search-url="<?= e($cardSearchUrl) ?>">
                                <input class="form-control card-picker-input" id="card_picker" type="search" autocomplete="off" placeholder="Search card name, set, or collector number" value="<?= e($selectedCardLabel) ?>" required>
                                <input id="card_id" name="card_id" type="hidden" value="<?= e((string) ($entry['card_id'] ?? '')) ?>">
                                <div class="card-picker-results list-group shadow-sm" role="listbox"></div>
                                <div class="form-text">Type at least 2 characters, then choose a result.</div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="fw-semibold"><?= e($entry['card_name']) ?></div>
                            <div class="text-muted small"><?= e($entry['card_set_name']) ?> #<?= e($entry['collector_number']) ?></div>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <label class="form-label" for="set_name">Display set name</label>
                        <input class="form-control" id="set_name" name="set_name" maxlength="255" value="<?= e((string) ($entry['set_name'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="accepted_condition">Accepted condition</label>
                        <input class="form-control" id="accepted_condition" name="accepted_condition" maxlength="50" value="<?= e((string) ($entry['accepted_condition'] ?? '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="cash_offer">Cash offer</label>
                        <input class="form-control" id="cash_offer" name="cash_offer" type="number" min="0" step="0.01" value="<?= e((string) $entry['cash_offer']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="credit_offer">Store credit offer</label>
                        <input class="form-control" id="credit_offer" name="credit_offer" type="number" min="0" step="0.01" value="<?= e((string) $entry['credit_offer']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="target_quantity">Target quantity</label>
                        <input class="form-control" id="target_quantity" name="target_quantity" type="number" min="1" value="<?= e((string) $entry['target_quantity']) ?>" required>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="1" <?= (int) ($entry['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="admin_notes">Admin notes</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="4" maxlength="2000"><?= e((string) ($entry['admin_notes'] ?? '')) ?></textarea>
                    </div>
                </div>

                <button class="btn btn-archive mt-4" type="submit"><?= e($buttonText) ?></button>
            </form>
        </div>
    </div>
</div>

<?php if (isset($cardSearchUrl)): ?>
    <script>
        (() => {
            const picker = document.querySelector('.card-picker');
            if (!picker) {
                return;
            }

            const searchUrl = picker.dataset.searchUrl;
            const input = picker.querySelector('.card-picker-input');
            const hidden = picker.querySelector('input[name="card_id"]');
            const results = picker.querySelector('.card-picker-results');
            let activeIndex = -1;
            let debounceTimer = null;
            let abortController = null;

            const clearResults = () => {
                results.innerHTML = '';
                results.classList.remove('is-open');
                activeIndex = -1;
            };

            const setActive = (index) => {
                const items = Array.from(results.querySelectorAll('button'));
                activeIndex = items.length === 0 ? -1 : Math.max(0, Math.min(index, items.length - 1));
                items.forEach((item, itemIndex) => {
                    item.classList.toggle('active', itemIndex === activeIndex);
                });
            };

            const chooseCard = (id, label) => {
                hidden.value = id;
                input.value = label;
                clearResults();
            };

            const renderResults = (cards) => {
                results.innerHTML = '';

                if (cards.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'list-group-item text-muted';
                    empty.textContent = 'No matching cards found';
                    results.appendChild(empty);
                    results.classList.add('is-open');
                    return;
                }

                cards.forEach((card) => {
                    const button = document.createElement('button');
                    button.className = 'list-group-item list-group-item-action';
                    button.type = 'button';
                    button.textContent = card.label;
                    button.addEventListener('click', () => chooseCard(card.id, card.label));
                    results.appendChild(button);
                });

                results.classList.add('is-open');
                setActive(0);
            };

            const searchCards = () => {
                const query = input.value.trim();
                hidden.value = '';

                if (query.length < 2) {
                    clearResults();
                    return;
                }

                if (abortController) {
                    abortController.abort();
                }
                abortController = new AbortController();

                fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' },
                    signal: abortController.signal,
                })
                    .then((response) => response.ok ? response.json() : [])
                    .then(renderResults)
                    .catch((error) => {
                        if (error.name !== 'AbortError') {
                            clearResults();
                        }
                    });
            };

            input.addEventListener('input', () => {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(searchCards, 180);
            });

            input.addEventListener('keydown', (event) => {
                const items = Array.from(results.querySelectorAll('button'));
                if (items.length === 0) {
                    return;
                }

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    setActive(activeIndex + 1);
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    setActive(activeIndex - 1);
                } else if (event.key === 'Enter' && activeIndex >= 0) {
                    event.preventDefault();
                    items[activeIndex].click();
                } else if (event.key === 'Escape') {
                    clearResults();
                }
            });

            document.addEventListener('click', (event) => {
                if (!picker.contains(event.target)) {
                    clearResults();
                }
            });
        })();
    </script>
<?php endif; ?>
