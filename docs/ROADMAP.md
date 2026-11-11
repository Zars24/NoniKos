# NoniKos — Roadmap

Status legend: Planned · In Progress · Implemented · Verified · Blocked

| Phase | Scope | Status |
|---|---|---|
| 0 | Discovery, audit, architecture, ERD, rules, permissions, security risks | Verified (docs) |
| 1 | Foundation, design tokens, UI components, responsive shell, dark mode | Verified |
| 2 | Auth, organizations, memberships, roles/permissions, tenant isolation | Verified |
| 3 | Properties, buildings, room types, rooms, facilities, photos, status history | Verified |
| 4 | Tenants, bookings, contracts, check-in/out, renewal, transfer | Verified |
| 5 | Billing engine, invoices, items, discounts, late fees, PDF | Verified |
| 6 | Payments, allocation, receipts, reconciliation, gateway adapter, webhooks | Implemented (gateway = sandbox adapter, no live credentials) |
| 7 | WhatsApp/notification templates, queue, retries, delivery log | Implemented (simulation adapter until provider credentials exist) |
| 8 | Finance reports, dashboard analytics, CSV/XLSX exports | Verified |
| 9 | Maintenance workflow, tenant portal | Verified |
| 10 | Activity log, backup/restore, scheduled jobs, settings, health probe | Verified except live restore replay (safeguards + checksum tested) |
| 11 | Hardening, regression, docs, deployment guide | Implemented; deployment steps not executed on a production host |
## Phase detail

| Phase | Evidence |
|---|---|
| 0 | `docs/*` blueprint documents; runtime audit in `docs/ARCHITECTURE.md` |
| 1 | `resources/views/layouts/*`, `components/ui/*`, `tailwind.config.js`; `npm run build` green |
| 2 | `tests/Feature/Auth/*`, `AuthorizationTest`, `TenantIsolationTest`, `PlatformAdminTest` |
| 3 | `RoomController`, `RoomStatusHistory`, `PageSmokeTest` renders property/room pages |
| 4 | `ContractLifecycleTest`, `TenantController` import + documents |
| 5 | `BillingTest` (idempotency, numbering, proration, late fee), invoice PDF |
| 6 | `PaymentTest` (overpayment, reversal, webhook signature/idempotency) |
| 7 | `NotificationService` + `notification_deliveries`; simulation adapter documented |
| 8 | `ReportingAndBackupTest` financial summary; export permission tests |
| 9 | `PortalTest` (own-data only, maintenance rules, private files) |
| 10 | `ReportingAndBackupTest` backup checksum/restore guards; `artisan schedule:list` |
| 11 | `PageSmokeTest`, full suite 82 passed; `docs/TESTING.md`, `docs/DEPLOYMENT.md` |

## Phase gates

Each phase must end with: migrations applied, relevant PHPUnit suite green, `npm run build` green, docs updated, and a status entry in `docs/IMPLEMENTATION_STATUS.md`.

## Explicitly out of scope for this build

- Subscription billing / paid plans (schema-ready via `organizations.plan`, no billing engine).
- Real payment-gateway and WhatsApp live credentials (adapters + webhook verification implemented, live activation blocked on credentials).
- Multi-database or schema-per-tenant isolation.
