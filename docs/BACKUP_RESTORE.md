# NoniKos — Backup & Restore

**Status:** implemented and exercised locally (MariaDB 10.4.25 via XAMPP). Restore has only been tested for safeguards and checksum verification, not replayed against production data.

## What is backed up

Database only. Uploaded files (`storage/app/private`) and the `public/build` assets are **not** included; back those up at the filesystem level.

## Storage

| Item | Location |
|---|---|
| Dump files | `storage/app/backups` (`backups` disk, private) |
| Metadata | `backup_records` table (filename, size, sha256 checksum, status, trigger, error) |

Dumps are never written to the public disk and are downloadable only through an authorized route.

## Creating a backup

CLI (preferred):

```powershell
C:\xampp\php\php.exe artisan nonikos:backup
```

Web (platform admin only): Backups → "Buat backup sekarang". The request runs synchronously, which is fine for small databases.

A dump contains every organization's rows, so backup routes live under `/platform/...` and require `users.is_platform_admin`. Organization owners and staff have no access, by design.

Implementation: `App\Services\BackupService::dump()` shells out to `mysqldump` with `--single-transaction --skip-lock-tables --routines --no-tablespaces`. On a non-MySQL connection (tests) it writes a logical snapshot instead, so tests never invoke the binary.

Binary paths come from `config/database.php`:

```dotenv
DB_DUMP_BINARY=C:\xampp\mysql\bin\mysqldump.exe
DB_MYSQL_BINARY=C:\xampp\mysql\bin\mysql.exe
```

If unset, `mysqldump`/`mysql` are resolved from `PATH`. A failed dump is recorded with `status=failed` and the real error; it never reports success.

## Restoring

Requirements:
1. Platform admin account (`users.is_platform_admin = true`).
2. Password confirmation (`password.confirm` middleware).
3. `confirm=RESTORE` in the request body.
4. A completed backup record whose file exists and whose sha256 matches the stored checksum.

Flow:
1. Checksum verified; mismatch aborts with "Checksum tidak cocok".
2. A **pre-restore safety backup** is created first.
3. The dump is piped into the `mysql` client.
4. Result is logged to `activity_logs` (`backup.restored` or `backup.restore_failed`).

**Restore overwrites current data.** Test on a disposable database first. This has not been run against a live dataset.

## Retention

```powershell
C:\xampp\php\php.exe artisan nonikos:prune-backups --days=30
```

Scheduled Sundays 23:45. Deletes completed dumps older than the window; recent and failed records are kept for audit.

## Recovery drill (do this before relying on it)

1. `artisan nonikos:backup`
2. Create a scratch database, point `DB_DATABASE` at it, run `migrate:fresh`.
3. Restore the dump into the scratch database with the `mysql` client.
4. Verify row counts for `organizations`, `invoices`, `payments` and that a login works.
5. Delete the scratch database.

## Gaps

- No off-site copy; a lost disk loses both data and backups.
- No application-level encryption of dumps (rely on disk encryption / access control).
- No automated restore verification (checksum only proves the file did not change).
- Uploaded files are outside the backup scope.
