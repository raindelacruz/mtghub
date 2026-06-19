<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="archive-card p-4">
            <h1 class="h3 mb-3">Create your MTGHub PH account</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/register')) ?>">
                <div class="row g-3">
                    <div class="col-12">
                        <h2 class="h5 mb-0">Personal information</h2>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="first_name">First Name</label>
                        <input class="form-control" id="first_name" name="first_name" value="<?= e($old['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="middle_initial">Middle I</label>
                        <input class="form-control" id="middle_initial" name="middle_initial" maxlength="5" value="<?= e($old['middle_initial'] ?? '') ?>">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="last_name">Last Name</label>
                        <input class="form-control" id="last_name" name="last_name" value="<?= e($old['last_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email Address</label>
                        <input class="form-control" id="email" name="email" type="email" value="<?= e($old['email'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="contact_number">Contact Number</label>
                        <input class="form-control" id="contact_number" name="contact_number" value="<?= e($old['contact_number'] ?? '') ?>" required>
                    </div>

                    <div class="col-12 pt-2">
                        <h2 class="h5 mb-0">Complete Address</h2>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="address_number">Number</label>
                        <input class="form-control js-complete-address" id="address_number" name="address_number" value="<?= e($old['address_number'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label" for="address_street">Bldg/Street/Unit</label>
                        <input class="form-control js-complete-address" id="address_street" name="address_street" value="<?= e($old['address_street'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="address_barangay">Barangay</label>
                        <input class="form-control js-complete-address" id="address_barangay" name="address_barangay" value="<?= e($old['address_barangay'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="address_province">Province</label>
                        <input class="form-control js-complete-address" id="address_province" name="address_province" value="<?= e($old['address_province'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="address_city">City</label>
                        <input class="form-control js-complete-address" id="address_city" name="address_city" value="<?= e($old['address_city'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="address_postal_code">Postal Code</label>
                        <input class="form-control js-complete-address" id="address_postal_code" name="address_postal_code" value="<?= e($old['address_postal_code'] ?? '') ?>" required>
                    </div>

                    <div class="col-12 pt-2">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                            <h2 class="h5 mb-0">Shipping Address</h2>
                            <div class="form-check">
                                <input class="form-check-input" id="shipping_same_as_complete" name="shipping_same_as_complete" type="checkbox" value="1" <?= !empty($old['shipping_same_as_complete']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="shipping_same_as_complete">Same as complete address</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="shipping_number">Number</label>
                        <input class="form-control js-shipping-address" id="shipping_number" name="shipping_number" value="<?= e($old['shipping_number'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label" for="shipping_street">Bldg/Street/Unit</label>
                        <input class="form-control js-shipping-address" id="shipping_street" name="shipping_street" value="<?= e($old['shipping_street'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="shipping_barangay">Barangay</label>
                        <input class="form-control js-shipping-address" id="shipping_barangay" name="shipping_barangay" value="<?= e($old['shipping_barangay'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="shipping_province">Province</label>
                        <input class="form-control js-shipping-address" id="shipping_province" name="shipping_province" value="<?= e($old['shipping_province'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="shipping_city">City</label>
                        <input class="form-control js-shipping-address" id="shipping_city" name="shipping_city" value="<?= e($old['shipping_city'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="shipping_postal_code">Postal Code</label>
                        <input class="form-control js-shipping-address" id="shipping_postal_code" name="shipping_postal_code" value="<?= e($old['shipping_postal_code'] ?? '') ?>" required>
                    </div>

                    <div class="col-12 pt-2">
                        <h2 class="h5 mb-0">Delivery and payment</h2>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="delivery_mode">Mode of Delivery</label>
                        <select class="form-select" id="delivery_mode" name="delivery_mode" required>
                            <option value="">Choose delivery mode</option>
                            <option value="lbc" <?= ($old['delivery_mode'] ?? '') === 'lbc' ? 'selected' : '' ?>>LBC</option>
                            <option value="meetup" <?= ($old['delivery_mode'] ?? '') === 'meetup' ? 'selected' : '' ?>>Meetup</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="payment_mode">Mode of Payment</label>
                        <select class="form-select" id="payment_mode" name="payment_mode" required>
                            <option value="gcash" <?= ($old['payment_mode'] ?? 'gcash') === 'gcash' ? 'selected' : '' ?>>GCash</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" id="password" name="password" type="password" minlength="8" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirm">Confirm password</label>
                        <input class="form-control" id="password_confirm" name="password_confirm" type="password" minlength="8" required>
                    </div>
                </div>

                <button class="btn btn-archive mt-4" type="submit">Register</button>
                <a class="btn btn-link mt-4" href="<?= e(url('/login')) ?>">I already have an account</a>
            </form>
        </div>
    </div>
</div>

<script>
(() => {
    const sameAddress = document.getElementById('shipping_same_as_complete');
    const pairs = {
        address_number: 'shipping_number',
        address_street: 'shipping_street',
        address_barangay: 'shipping_barangay',
        address_province: 'shipping_province',
        address_city: 'shipping_city',
        address_postal_code: 'shipping_postal_code',
    };

    const copyAddress = () => {
        Object.entries(pairs).forEach(([sourceId, targetId]) => {
            const source = document.getElementById(sourceId);
            const target = document.getElementById(targetId);
            if (source && target) {
                target.value = source.value;
                target.readOnly = sameAddress.checked;
            }
        });
    };

    if (!sameAddress) {
        return;
    }

    sameAddress.addEventListener('change', copyAddress);
    document.querySelectorAll('.js-complete-address').forEach((field) => {
        field.addEventListener('input', () => {
            if (sameAddress.checked) {
                copyAddress();
            }
        });
    });

    if (sameAddress.checked) {
        copyAddress();
    }
})();
</script>
