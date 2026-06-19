<div class="row justify-content-center"><div class="col-lg-7"><div class="archive-card p-4">
    <h1 class="h3">Submit a report</h1>
    <?php $subjectLabel = $type === 'user' ? $subject['username'] : ($type === 'listing' ? $subject['card_name'] . ' listing by ' . $subject['username'] : 'Review by ' . $subject['reviewer_username'] . ' for ' . $subject['seller_username']); ?>
    <p class="text-muted">Reporting: <strong><?= e($subjectLabel) ?></strong></p>
    <?php if ($errors !== []): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" action="<?= e(url('/reports/store?type=' . urlencode($type) . '&id=' . (int) $subject['id'])) ?>">
        <div class="mb-3"><label class="form-label" for="reason">Reason</label><select class="form-select" id="reason" name="reason" required>
            <option value="">Choose reason</option><?php foreach (['suspected_scam'=>'Suspected scam','counterfeit'=>'Counterfeit concern','harassment'=>'Harassment','inappropriate'=>'Inappropriate content','other'=>'Other'] as $value=>$label): ?><option value="<?= e($value) ?>" <?= ($old['reason'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select></div>
        <div class="mb-3"><label class="form-label" for="details">Details</label><textarea class="form-control" id="details" name="details" minlength="10" maxlength="2000" rows="6" required><?= e($old['details'] ?? '') ?></textarea></div>
        <button class="btn btn-danger" type="submit">Submit report</button>
    </form>
</div></div></div>
