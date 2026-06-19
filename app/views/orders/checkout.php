<div class="mb-4">
    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/cart')) ?>">Back to cart</a>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="archive-card p-4">
            <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Checkout</p>
            <h1 class="h3 mb-4">Payment and logistics</h1>

            <?php if ($errors !== []): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/orders/place')) ?>">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="buyer_location">Your location</label>
                        <input class="form-control" id="buyer_location" name="buyer_location" value="<?= e($checkout['buyer_location']) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="logistics_method">Logistics</label>
                        <select class="form-select" id="logistics_method" name="logistics_method" required>
                            <option value="meetup" <?= $checkout['logistics_method'] === 'meetup' ? 'selected' : '' ?>>Meetup - PHP 0.00</option>
                            <option value="lbc" <?= $checkout['logistics_method'] === 'lbc' ? 'selected' : '' ?>>LBC shipping - PHP 100.00</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="delivery_details">Meetup or shipping details</label>
                        <input class="form-control" id="delivery_details" name="delivery_details" value="<?= e($checkout['delivery_details']) ?>" placeholder="Meetup place/time or LBC recipient/address details" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="external_payment_method">Cash/GCash/bank method</label>
                        <input class="form-control" id="external_payment_method" name="external_payment_method" value="<?= e($checkout['external_payment_method']) ?>" placeholder="GCash, bank transfer, cash on meetup">
                        <div class="form-text">After placing the order, submit your payment reference from the order page within 24 hours.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="store_credit_to_use">Store Credit To Use</label>
                        <input class="form-control" id="store_credit_to_use" name="store_credit_to_use" type="number" min="0" step="0.01" max="<?= e(number_format((float) min($walletBalance, $summary['total']), 2, '.', '')) ?>" data-wallet-balance="<?= e(number_format((float) $walletBalance, 2, '.', '')) ?>" value="<?= e($checkout['store_credit_to_use']) ?>">
                        <div class="form-text">Available Store Credit: ₱<?= e(number_format((float) $walletBalance, 2)) ?>. Non-withdrawable MTGHub Credit.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="notes">Notes to seller</label>
                        <textarea class="form-control" id="notes" name="notes" rows="4"><?= e($checkout['notes']) ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-archive" type="submit" <?= $errors !== [] ? 'disabled' : '' ?>>Place order</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="archive-card p-4">
            <p class="text-uppercase fw-semibold archive-kicker-dark mb-1">Order summary</p>
            <h2 class="h5 mb-3">Items</h2>

            <?php foreach ($items as $item): ?>
                <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                    <div>
                        <div class="fw-semibold"><?= e($item['card_name']) ?></div>
                        <div class="small text-muted"><?= e($item['seller_username']) ?> | Qty <?= e((string) $item['quantity']) ?></div>
                    </div>
                    <div class="text-end">PHP <?= e(number_format((float) $item['price_php'] * (int) $item['quantity'], 2)) ?></div>
                </div>
            <?php endforeach; ?>

            <dl class="row mt-3 mb-0" id="checkout-summary" data-subtotal="<?= e(number_format((float) $summary['subtotal'], 2, '.', '')) ?>">
                <dt class="col-7">Cards subtotal</dt>
                <dd class="col-5 text-end">PHP <?= e(number_format((float) $summary['subtotal'], 2)) ?></dd>
                <dt class="col-7">Logistics fee</dt>
                <dd class="col-5 text-end" id="checkout-shipping">PHP <?= e(number_format((float) $summary['shipping'], 2)) ?></dd>
                <dt class="col-7 fs-5">Total payment</dt>
                <dd class="col-5 text-end fs-5 fw-bold" id="checkout-total">PHP <?= e(number_format((float) $summary['total'], 2)) ?></dd>
                <dt class="col-7">Available Store Credit</dt>
                <dd class="col-5 text-end">₱<?= e(number_format((float) $walletBalance, 2)) ?></dd>
                <dt class="col-7">Store Credit Used</dt>
                <dd class="col-5 text-end" id="checkout-store-credit">₱<?= e(number_format((float) $summary['store_credit_to_use'], 2)) ?></dd>
                <dt class="col-7">Remaining Amount Due</dt>
                <dd class="col-5 text-end fw-bold" id="checkout-cash-due">₱<?= e(number_format((float) $summary['cash_due'], 2)) ?></dd>
            </dl>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const logistics = document.getElementById('logistics_method');
    const summary = document.getElementById('checkout-summary');
    const shipping = document.getElementById('checkout-shipping');
    const total = document.getElementById('checkout-total');
    const storeCreditInput = document.getElementById('store_credit_to_use');
    const storeCredit = document.getElementById('checkout-store-credit');
    const cashDue = document.getElementById('checkout-cash-due');

    if (!logistics || !summary || !shipping || !total || !storeCreditInput || !storeCredit || !cashDue) {
        return;
    }

    const subtotal = Number(summary.dataset.subtotal || 0);
    const walletBalance = Number(storeCreditInput.dataset.walletBalance || 0);
    const formatPhp = function (value) {
        return '₱' + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    const updateTotals = function () {
        const shippingFee = logistics.value === 'lbc' ? 100 : 0;
        const orderTotal = subtotal + shippingFee;
        const requestedCredit = Math.max(0, Number(storeCreditInput.value || 0));
        const appliedCredit = Math.min(requestedCredit, walletBalance, orderTotal);
        storeCreditInput.max = String(Math.min(walletBalance, orderTotal).toFixed(2));
        shipping.textContent = formatPhp(shippingFee);
        total.textContent = formatPhp(orderTotal);
        storeCredit.textContent = formatPhp(appliedCredit);
        cashDue.textContent = formatPhp(Math.max(0, orderTotal - appliedCredit));
    };

    logistics.addEventListener('change', updateTotals);
    storeCreditInput.addEventListener('input', updateTotals);
    updateTotals();
});
</script>
