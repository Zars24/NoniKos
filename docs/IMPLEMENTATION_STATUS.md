# NoniKos — Implementation Status

**Last updated:** 2026-10-19 (end of build session)
**Environment:** Windows, XAMPP PHP 8.1.10, MariaDB 10.4.25 (`nonikos`), Node 20.17.0. Git not initialized.

This is the continuation log. Read it first when resuming work.

## Verification snapshot

| Check | Command | Result |
|---|---|---|
| Test suite | `php artisan test` | **82 passed (246 assertions)**, ~6.3s |
| Migrations | `php artisan migrate:fresh --seed --force` | All 10 migrations applied, demo data seeded |
| Frontend build | `npm run build` | Vite 4.5.14, built in 1.37s |
| Scheduler | `php artisan schedule:list` | 8 tasks registered |
| Backup (real MariaDB) | `php artisan nonikos:backup` | 64.1 KB dump, checksum recorded |
| Invoice run | `php artisan nonikos:generate-invoices --month=2026-11` | 3 created, then 0 created / 3 skipped on re-run |

## Phase status

| Phase | Status | Notes |
|---|---|---|
| 0 Discovery/docs | Done | All blueprint docs in `docs/` |
| 1 Foundation/design | Done | Layout, tokens, components, dark mode, responsive nav |
| 2 Auth/org/permissions | Done | Breeze + custom multi-tenancy + permission gates; tests |
| 3 Properties/rooms | Done | Buildings, room types, photos, status history |
| 4 Tenants/bookings/contracts | Done | Import, documents, conversion, transfer, renew, terminate |
| 5 Billing/invoices | Done | Idempotent generation, numbering, prorating, late fees, PDF |
| 6 Payments | Done (sandbox) | Manual + online path; live gateway blocked on credentials |
| 7 Notifications | Done (simulation) | Templates, deliveries, scheduler; no live WhatsApp |
| 8 Finance/reports | Done | Summary, series, aging, CSV + XLSX exports |
| 9 Maintenance/portal | Done | Workflow + tenant portal with privacy tests |
| 10 Ops/reliability | Done | Activity log (org-scoped), platform-only backups, scheduler, settings, `/health` probe, auth audit trail |
| 11 Hardening/docs | Done with documented residual risks | See `docs/SECURITY.md` |

## What is deliberately not implemented

| Item | Reason | Where documented |
|---|---|---|
| Subscription billing / plan enforcement | Out of scope; `organizations.plan` is descriptive | `docs/ROADMAP.md` |
| Live payment gateway | No credentials available | `docs/INTEGRATIONS.md` |
| Live WhatsApp sending | No credentials; templates need provider approval | `docs/INTEGRATIONS.md` |
| 2FA | Not built | `docs/SECURITY.md` |
| Browser E2E tests | Not installed | `docs/TESTING.md` |
| Off-site backup replication | Not built | `docs/BACKUP_RESTORE.md` |
| Laravel 12 upgrade | Requires PHP ≥ 8.2 | `docs/ARCHITECTURE.md` |

## Blockers requiring user action

1. **Payment gateway credentials** — set `PAYMENT_GATEWAY_KEY`, `PAYMENT_GATEWAY_BASE_URL`, `PAYMENT_GATEWAY_WEBHOOK_SECRET`. Until then every payment link is labelled sandbox and no charge is possible.
2. **WhatsApp credentials** — set `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`. Until then messages are logged and recorded as `failed` with an explicit simulation error.
3. **PHP upgrade** — moving to Laravel 12 (fixes two advisories) requires PHP ≥ 8.2.

## Decisions worth remembering

- Single database, shared schema, `organization_id` + global scope (`TenantContext`). Not schema-per-tenant.
- Organization selection comes from the session, validated against an active membership; request input is never trusted.
- Codes (`INV/...`, `TNT-...`, `AGR-...`, `BKG-...`) are globally unique; the DB enforces it, so `CodeGenerator` counts globally.
- Invoice numbering runs inside a transaction holding an organization row lock.
- Payments allocate to a single invoice; overpayment rejected; reversal is a status change, never a delete.
- Backup/restore uses the `mysqldump`/`mysql` binaries, configured via `DB_DUMP_BINARY`/`DB_MYSQL_BINARY` (set to XAMPP absolute paths in `.env`).
- Backups are whole-database and therefore a platform-admin operation, not an organization permission.
- App timezone is `Asia/Jakarta` and locale `id`, both env-driven (`APP_TIMEZONE`, `APP_LOCALE`); invoice periods and reminders depend on the timezone.
- Integration adapters are bound in `AppServiceProvider`; sandbox/simulation modes must never report success.
- Platform admin is a separate `users.is_platform_admin` axis, not an organization role.

## Bugs found and fixed this session

- `UserFactory` used a hard-coded bcrypt hash that failed `Hash::verifyConfiguration()` under `BCRYPT_ROUNDS=4`.
- `UserFactory` did not set `status`, so `isActive()` returned false for freshly created users until refreshed.
- `RegisteredUserController` closure did not capture `$request`.
- Profile routes were inside the `organization` middleware group, blocking platform admins and membership-less users; moved to `auth` only.
- `ProfileController::update` wrote `theme_preference` as null (NOT NULL column) when omitted.
- `OrganizationMembership::properties()` used default pivot keys instead of `membership_id`.
- Policies were only registered for `RentalAgreement`; every other `authorize()` call fell through to an unregistered policy and denied.
- `CodeGenerator` scoped counting per organization while the unique index is global → duplicate code errors.
- `config/database.php` had duplicate mysql keys and dropped `options`.
- Webhook endpoint was blocked by CSRF; added `webhooks/payments/*` to the exception list.
- Global search leaked tenant records to tenant-role users; now scoped.
- `ActivityLogController` listed every organization's rows (`ActivityLog` has no global scope); now scoped to the organization plus its members' auth events.
- Backup UI was gated by an owner permission, but a dump contains every organization's rows — moved to `/platform/backups` behind `users.is_platform_admin` and `backups.*` removed from the organization matrix.
- Report export was vulnerable to spreadsheet formula injection (`=`, `+`, `-`, `@`); cells are now neutralised in CSV and XLSX.
- Registration, forgot-password, reset-password and confirm-password had no route throttle.
- `config/app.php` hard-coded `UTC`/`en`; now env-driven with `Asia/Jakarta`/`id` defaults, which invoice periods and reminders depend on.
- `AuthenticatedSessionController` redirected to `/dashboard` for everyone, so tenant logins landed on a 403; now redirects to the role-aware `home` route.
- Running the suite while `config:cache` was active made `RefreshDatabase` wipe the real MariaDB database. `tests/CreatesApplication.php` now refuses to boot when a cached config exists.
- Seeder created tenants without portal users, so the tenant portal was untestable from demo data.
- Phase 10 gaps: added `/health` probe (DB + private-disk write, booleans only), structured exception logging, and a login/logout/failed-login audit trail.

## Suggested next steps

1. Add a browser E2E pass (Dusk or Playwright) for login → contract → invoice → payment.
2. Add 2FA for owner and platform-admin accounts.
3. Queue outbound integration calls (`QUEUE_CONNECTION=database` + worker) before enabling live providers.
4. Add off-site backup copy and a scheduled restore-verification job.
5. When PHP ≥ 8.2 is available, upgrade to Laravel 12 and re-run the suite.

---

## Phase records

Condensed from the format required by `docs/README.md` §17. All phases were implemented in one session; the evidence column is what actually ran.

### Phase 0 — Discovery, audit, blueprint
- **Status:** Verified (docs)
- **Implemented:** architecture, ERD, database design, business rules, permission matrix, security risks, roadmap
- **Files:** `docs/ARCHITECTURE.md`, `ERD.md`, `DATABASE_DESIGN.md`, `BUSINESS_RULES.md`, `PERMISSIONS.md`, `SECURITY.md`, `DESIGN_SYSTEM.md`, `ROADMAP.md`
- **Database:** none
- **Tests:** n/a
- **Known issues:** Git not initialized, so no change history exists

### Phase 1 — Foundation and design system
- **Status:** Verified
- **Implemented:** app shell, sidebar, topbar, theme manager (light/dark/system), UI primitives, Indonesian auth screens
- **Files:** `resources/views/layouts/*`, `resources/views/components/ui/*`, `resources/css/app.css`, `tailwind.config.js`, `resources/js/app.js`
- **Tests:** `npm run build` green; `PageSmokeTest` renders the shell
- **Security:** Blade escaping only; no raw user HTML

### Phase 2 — Auth, organizations, permissions
- **Status:** Verified
- **Implemented:** registration with organization provisioning, login/reset/verify, memberships, organization switch, permission gates, policies, tenant isolation, platform admin
- **Files:** `app/Support/{TenantContext,Permissions}.php`, `app/Models/Concerns/BelongsToOrganization.php`, `app/Http/Middleware/Ensure*.php`, `app/Providers/AuthServiceProvider.php`, `app/Policies/*`
- **Database:** `organizations`, `organization_memberships`, `membership_properties`, user profile fields
- **Tests:** `Auth/*`, `AuthorizationTest`, `TenantIsolationTest`, `PlatformAdminTest`
- **Known issues:** no 2FA

### Phase 3 — Properties and rooms
- **Status:** Verified
- **Implemented:** properties, buildings, room types, rooms, photos (private disk), status history, guarded status transitions
- **Files:** `PropertyController`, `RoomController`, related models/views
- **Tests:** `PageSmokeTest`, `PortalTest` (photo authorization)
- **Security:** private file serving through an authorized route only

### Phase 4 — Tenants, bookings, contracts
- **Status:** Verified
- **Implemented:** tenants, CSV import, documents, bookings, agreement lifecycle (create/renew/terminate/check-in/out/transfer), overlap prevention
- **Files:** `TenantController`, `BookingController`, `AgreementController`, `ContractService`
- **Tests:** `ContractLifecycleTest`, `PortalTest` (document scoping)
- **Known issues:** import supports CSV only, not XLSX

### Phase 5 — Billing and invoices
- **Status:** Verified
- **Implemented:** idempotent period billing, numbering under org lock, proration, discounts, late fees, cancellation guard, adjustments, PDF
- **Files:** `BillingService`, `InvoiceNumberGenerator`, `InvoiceController`
- **Tests:** `BillingTest`
- **Known issues:** proration is day-count only (documented in code)

### Phase 6 — Payments
- **Status:** Implemented (sandbox only)
- **Implemented:** manual payments, partial allocation, overpayment rejection, reversal with audit, receipts, payment links, HMAC webhooks with idempotency
- **Files:** `PaymentService`, `PaymentController`, `PaymentLinkController`, `PaymentWebhookController`, `Integrations/*`
- **Tests:** `PaymentTest`
- **External dependencies:** `PAYMENT_GATEWAY_KEY`, `PAYMENT_GATEWAY_BASE_URL`, `PAYMENT_GATEWAY_WEBHOOK_SECRET`

### Phase 7 — Notifications
- **Status:** Implemented (simulation only)
- **Implemented:** per-organization templates, delivery log, retry-safe sends, scheduled reminders
- **Files:** `NotificationService`, `Integrations/WhatsAppCloudSender.php`, `MessageTemplate`, `NotificationDelivery`
- **Tests:** covered indirectly by scheduler runs; no provider test
- **External dependencies:** `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, approved templates

### Phase 8 — Finance and reporting
- **Status:** Verified
- **Implemented:** dashboard metrics, 6-month series, aging, property filter, CSV/XLSX exports with separate permission
- **Files:** `ReportService`, `ReportController`, `DashboardController`
- **Tests:** `ReportingAndBackupTest`, export permission in `AuthorizationTest`

### Phase 9 — Maintenance and portal
- **Status:** Verified
- **Implemented:** maintenance workflow with comments/assignment/costs; tenant portal for invoices, payments, complaints, notifications
- **Files:** `MaintenanceController`, `Portal/PortalController`
- **Tests:** `PortalTest`

### Phase 10 — Operations and reliability
- **Status:** Verified except live restore replay
- **Implemented:** activity log (scoped to the organization and its members' auth events), settings, platform-admin database backups (create/download/restore + safety backup), 8 scheduled commands, `/health` probe (DB + private-disk write, booleans only), structured exception logging, auth audit trail (login/logout/failed)
- **Files:** `ActivityLogController`, `SettingsController`, `BackupController`, `BackupService`, `HealthController`, `app/Exceptions/Handler.php`, `app/Providers/EventServiceProvider.php`, `app/Console/*`
- **Tests:** `ReportingAndBackupTest`, `HealthTest`; real `nonikos:backup` produced a 64.1 KB dump; `/health` returned `{"status":"ok"}` against the live MariaDB
- **Known issues:** no off-site copy, no encryption of dumps

### Phase 11 — Hardening and docs
- **Status:** Implemented
- **Implemented:** policy registration fix, search scoping, CSRF exemption for webhooks, activity-log isolation, platform-only backups, CSV/XLSX formula-injection neutralisation, auth route throttles, env-driven timezone/locale, print stylesheet for reports, full regression suite, all documentation
- **Tests:** 82 passed (246 assertions); `npm run build` green
- **Known issues:** deployment steps documented but not executed on a production host; Laravel 10 advisories remain until PHP ≥ 8.2
