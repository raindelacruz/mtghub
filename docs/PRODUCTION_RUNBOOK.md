# MTGHub Production Runbook

## Deploy and migrate

1. Put the site in maintenance mode at the web-server layer.
2. Run `C:\xampp\php\php.exe scripts\backup.php`.
3. Run `C:\xampp\php\php.exe scripts\migrate.php`.
4. Run the automated test commands listed in `tests/README.md`.
5. Remove maintenance mode and inspect `/admin/operations`.

Applied migrations are immutable. The runner refuses to continue if an applied file's SHA-256 checksum changes. Add a new numbered migration instead of editing one already deployed.

## Scheduled jobs

- Every hour: `scripts\expire_orders.php`
- Every day at 02:00: `scripts\backup.php`
- Every day at 03:00: `scripts\process_account_deletions.php`
- Every day at 03:30: `scripts\cleanup_retention.php`
- Weekly after backup: `scripts\verify_restore.php`

Backups are stored outside the public directory in `storage/backups`, checksummed, and retained locally for 30 days. Copy encrypted backups to a separate restricted host or storage account; a local disk alone is not disaster recovery.

## Incident response

1. Check `/admin/operations` and `storage/logs/app-YYYY-MM-DD.log` using the request ID shown to the user.
2. For transaction incidents, freeze the order with a dispute before changing balances.
3. Never edit wallet balances without the admin wallet workflow and an audit note.
4. Preserve affected logs, proof files, messages, and the latest backup.
5. Rotate credentials if compromise is suspected and notify affected users as required by applicable law.

## Retention

Run account deletion processing daily. Security logs should be removed after 90 days. Closed-order payment-proof files should be removed after 180 days unless attached to a dispute or legal hold. Anonymized transaction, wallet, dispute, moderation, and audit records may be retained for up to seven years.
