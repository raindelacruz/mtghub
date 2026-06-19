# Automated production gate

Run from the project root with XAMPP MySQL active:

```powershell
C:\xampp\php\php.exe scripts\migrate.php
C:\xampp\php\php.exe tests\production_readiness.php
C:\xampp\php\php.exe tests\phase6_smoke.php
C:\xampp\php\php.exe scripts\backup.php
C:\xampp\php\php.exe scripts\verify_restore.php
```

`production_readiness.php` uses disposable users, listings, carts, orders, wallets, and uploads. It validates password and CSRF behavior, participant authorization, checkout accounting, inventory reservation/restoration, valid order transitions, settlement, cancellation refund idempotency, upload MIME/dimension/size/path handling, and deletion scheduling. `phase6_smoke.php` validates disputes, full refund allocation, wallet reversals, reviews, reporting, and moderation.

The scripts return non-zero on failure and clean up disposable database records. Run them in a dedicated staging database before every production release.
