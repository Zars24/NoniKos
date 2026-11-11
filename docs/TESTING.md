# NoniKos — Testing

**Status:** automated suite green on 2026-10-19. Numbers below are from an actual run, not estimates.

## How to run

```powershell
C:\xampp\php\php.exe artisan test
```

Configuration: `phpunit.xml` uses SQLite `:memory:`, `BCRYPT_ROUNDS=4`, array cache/session, `sync` queue. No external services are contacted.

**Never run the suite with a cached config.** `artisan config:cache` bakes `.env` into
`bootstrap/cache/config.php` and overrides `phpunit.xml`, so `RefreshDatabase` would
migrate and wipe the real database. `tests/CreatesApplication.php` refuses to boot in
that state; run `php artisan optimize:clear` first.

Last result:

```
Tests:    82 passed (246 assertions)
Duration: ~6.3s
```

Frontend build check:

```powershell
npm run build
```

Last result: Vite 4.5.14, `app-a0c91c4b.css` 55.57 kB, `app-4cfe280c.js` 56.25 kB.

## Test layout

| File | Covers |
|---|---|
| `tests/TestCase.php` | Shared fixture: provisions an organization with owner/manager/finance/operations/tenant users, a property, a room and a tenant. |
| `Feature/Auth/*` | Registration, login, logout, password reset/update, e-mail verification, password confirmation. |
| `Feature/AuthorizationTest.php` | Permission matrix, role gates on staff/backups/activity/reports/exports/rooms, tenant confinement to portal, inactive user denial. |
| `Feature/TenantIsolationTest.php` | Global scope per organization, cross-organization invoice access, organization switch validation, missing-context guard. |
| `Feature/BillingTest.php` | Billing idempotency, invoice numbering, double-booking rejection, late fee applied once, status recomputation after payment. |
| `Feature/PaymentTest.php` | Overpayment rejection, draft invoice rejection, reversal audit + balance restore, double reversal rejection, webhook signature/idempotency/amount mismatch. |
| `Feature/PortalTest.php` | Tenant sees only own invoices and notifications, tenant login lands on the portal, cross-tenant invoice/PDF denial, maintenance submission rules, private room photo access, tenant document scoping. |
| `Feature/ReportingAndBackupTest.php` | Financial summary definitions, draft/cancelled exclusion, backup checksum, platform-admin-only backup access, restore confirmation phrase, tampered backup rejection. |
| `Feature/HealthTest.php` | Public health probe returns 200 with booleans and leaks no schema or database name. |
| `Feature/PlatformAdminTest.php` | Platform listing, denial for regular owners, suspend action, home redirect. |
| `Feature/ContractLifecycleTest.php` | Agreement creation/room occupancy, termination, booking conversion idempotency, room transfer, renewal guard, converted-booking deletion guard. |
| `Feature/SearchTest.php` | Global search must not expose other tenants to a tenant-role user. |
| `Feature/PageSmokeTest.php` | Renders every admin page and every portal page once to catch Blade/runtime errors. |

## Known gaps

- No browser/E2E test (no Dusk/Laravel Breeze browser suite installed).
- PDF rendering is exercised only as a response (DomPDF), not pixel-verified.
- XLSX export is permission-tested; file contents are not asserted.
- Restore is tested for safeguards and checksum, not against a live MySQL dump replay.
- Webhook tests use the local HMAC adapter, not a real provider.
- No load/performance testing.

## Rules

- Never disable a test or validation to make the suite pass.
- New money, authorization or isolation behaviour requires a test in the same change.
- If a test cannot run because a dependency or service is missing, document the blocker instead of skipping silently.
