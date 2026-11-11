# NoniKos — Changelog

All notable changes. Dates are the working date of this build (2026-10-19). The project has no git history; this file is the record.

## [Unreleased] — initial implementation

### Added — foundation
- Laravel 10.50.3 + Breeze (Blade) on PHP 8.1.10; Tailwind 3, Alpine 3, Vite 4.
- Design system: tokens, buttons, inputs, cards, badges, alerts, modal, pagination, empty state, page header; responsive sidebar; light/dark/system theme with persisted preference.
- Indonesian UI copy across auth and application screens.

### Added — multi-tenancy and authorization
- `organizations`, `organization_memberships`, `membership_properties`.
- `TenantContext` + `BelongsToOrganization` global scope; organization chosen from validated membership, never from request input.
- Permission catalogue (`App\Support\Permissions`) for owner/manager/finance/operations/tenant, exposed as gates; nine policies.
- `EnsureOrganization`, `EnsurePlatformAdmin`, `EnsureTenantPortal` middleware.
- Platform admin area: organization list + suspend/reactivate.

### Added — property and tenant domain
- Properties, buildings, room types, rooms, room photos (private disk), room status history.
- Tenants, tenant documents (private disk), CSV import with all-or-nothing validation.
- Bookings, rental agreements, check-in/out, renewal, termination, room transfer, overlap prevention.
- Contract PDF.

### Added — billing and payments
- Invoices with items, idempotent period generation, per-organization numbering under row lock, prorating, discounts, late fees, cancellation guard, adjustments.
- Payments with partial allocation, overpayment rejection, reversal with audit trail, receipts.
- Payment links + HMAC-verified webhooks with event idempotency, currency and amount checks.
- Invoice/agreement/receipt PDFs (DomPDF).

### Added — finance and operations
- Dashboard metrics, 6-month series, aging buckets, due-soon and ending-contract lists.
- Finance report with property filter and 24-month range cap; CSV and XLSX exports with a separate export permission.
- Expenses and expense categories; maintenance requests with workflow, comments, assignment and costs.
- Message templates and notification delivery log.

### Added — tenant portal
- Dashboard, invoices, invoice detail/PDF, payment link creation, payment history, maintenance submission.

### Added — administration
- Staff management with role changes, property assignment, deactivation with session revocation, last-owner protection.
- Activity log, organization settings, platform-admin database backup create/download/restore with checksum and pre-restore safety backup.
- Console commands + scheduler for invoices, late fees, overdue marking, reminders, contract expiry, notifications, backups, pruning.

### Added — documentation
- `PRODUCT_REQUIREMENTS`, `ARCHITECTURE`, `ERD`, `DATABASE_DESIGN`, `BUSINESS_RULES`, `PERMISSIONS`, `DESIGN_SYSTEM`, `ROADMAP`, `SECURITY`, `TESTING`, `INTEGRATIONS`, `BACKUP_RESTORE`, `DEPLOYMENT`, `IMPLEMENTATION_STATUS`, `CHANGELOG`, plus root `AGENTS.md`.

### Fixed during implementation
- `UserFactory` hard-coded a bcrypt hash incompatible with the test cost factor; now hashes via `Hash::make`.
- `RegisteredUserController` closure did not capture `$request`.
- Profile routes sat behind the organization middleware, blocking platform admins and users without a membership.
- `OrganizationMembership::properties()` used the wrong pivot keys (`membership_properties.membership_id`).
- Policies for maintenance/booking/expense/property/room/tenant/invoice/payment were not registered, so `viewAny` silently denied.
- `CodeGenerator` produced duplicate codes across organizations while the schema enforces global uniqueness.
- `config/database.php` had duplicated mysql keys; backup binary paths were unreachable.
- Webhook route was blocked by CSRF for provider callbacks.

### Security
- Private disks for documents, proofs, photos, backups; authorized download routes only.
- HMAC webhook verification fails closed when no secret is configured.
- Input normalisation strips CR/LF from name/email/phone fields.
- Deactivated users and memberships denied at middleware level.

### Known limitations
- Payment gateway and WhatsApp run in sandbox/simulation mode; no live credentials.
- Laravel 10.50.3 advisories require PHP ≥ 8.2 + Laravel 12 to fully resolve.
- No 2FA, no browser E2E tests, no off-site backup replication, no subscription billing.
