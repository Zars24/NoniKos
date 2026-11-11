# NoniKos — Product Requirements

**Status:** implemented baseline. This document records what the product does today, not aspirations.

Source of truth for scope: `docs/README.md`. Business rules: `docs/BUSINESS_RULES.md`. Permissions: `docs/PERMISSIONS.md`.

## Target users

| Persona | Needs | Role |
|---|---|---|
| Owner / pemilik kos | Oversight, revenue, expenses, staffing, activity log | `owner` |
| Manager / pengelola | Day-to-day operations, occupancy, contracts | `manager` |
| Finance / admin keuangan | Invoices, payments, reports, exports | `finance` |
| Operations / teknisi | Room status, maintenance queue | `operations` |
| Tenant / penyewa | Own invoices, payments, complaints | `tenant` |
| Platform admin | Cross-organization oversight (suspend only) | `users.is_platform_admin` |

## Functional scope (implemented)

### Authentication and organizations
- Register creates a user + organization + owner membership in one transaction.
- Login, logout, password reset, password confirmation, e-mail verification (optional).
- Users can belong to multiple organizations; the active organization comes from session and is validated against an active membership.
- Organization switcher only lists memberships the user actually has.
- Organization settings: name, contact, invoice prefix, due days, late-fee percent, timezone.

### Properties and rooms
- Properties with code, address, contact, default due days, active flag.
- Buildings, room types, rooms (number, floor, price, deposit, billing cycle, facilities, photos, active flag).
- Room statuses: `available`, `reserved`, `occupied`, `maintenance`, `unavailable`, with an append-only status history.
- Status changes are guarded: `occupied` only via an active contract; a room with an active contract cannot be set to any other status.
- Rooms with contract history are deactivated, never hard-deleted.

### Tenants
- Tenant records with code, contact, identity, emergency contact, status.
- CSV import with header mapping, per-row validation, duplicate e-mail detection, all-or-nothing writes.
- Documents uploaded to private storage, downloaded only through an authorized route.
- Tenants with financial history are deactivated instead of deleted.

### Bookings and contracts
- Bookings: pending → confirmed → converted, or cancelled/expired.
- Booking conversion creates the rental agreement and marks the booking converted (idempotent).
- Agreements: room, tenant, start/end date, price, deposit, billing cycle, due days, check-in/out, renewal, termination, room transfer.
- Overlapping active agreements on the same room are rejected.
- PDF contract generation.

### Billing
- Invoice per agreement period, idempotent via unique `(rental_agreement_id, period_start)`.
- Invoice numbers: `INV/{ORGSLUG}/{YYYYMM}/{SEQ}`, generated under an organization row lock.
- First period prorated by remaining days in the start month.
- Manual invoices, discount editing while draft, late fees applied once after the due date, cancellation guarded by payments.
- Invoice statuses: `draft`, `issued`, `partially_paid`, `paid`, `overdue`, `cancelled`.
- Financial adjustments (correction, deposit refund, credit, write-off) recorded as rows.
- Invoice PDF.

### Payments
- Manual payments (cash, bank transfer, other) allocated to one invoice; partial payments allowed; overpayment rejected.
- Payment reversal keeps the row, records the reason, recomputes the invoice balance and status.
- Receipt PDF.
- Online payment links + signed webhook handling (sandbox adapter by default).

### Finance
- Dashboard: occupancy, billed, received, outstanding, overdue, 6-month series, due-soon list, recent payments, ending contracts.
- Finance report: summary, monthly series, aging buckets, property filter, date range capped at 24 months.
- Exports: CSV (native) and XLSX (OpenSpout) gated by a separate export permission.

### Operations
- Maintenance requests with category, priority, status workflow, assignment, comments, costs.
- Expenses with categories, property link, date, proof path.
- Message templates per organization (WhatsApp bodies with `{placeholders}`).
- Notification deliveries record the true provider outcome; nothing is marked sent without confirmation.

### Tenant portal
- Dashboard with active contract, balance, recent invoices/payments.
- Invoice list/detail/PDF, online payment link creation.
- Maintenance request submission (requires an active contract).
- Notification history for the tenant's own deliveries.
- All portal reads are resolved from the authenticated user, never from a URL id.
- Tenant logins land on the portal, not on a staff dashboard.

### Administration
- Staff management (owner only): create user + membership, change role, property assignment, deactivate with session revocation, last-owner protection.
- Activity log (owner only) with filters, including login/logout/failed-login events for the organization's members.
- Health probe `GET /health` (unauthenticated, `60/min`) returning `ok`/`degraded` plus booleans for database and private-disk writes; never exposes counts, names, or error text.
- Backups (platform admin only): create, download, restore with confirmation phrase + password + pre-restore safety backup. Whole-database dumps are never exposed to organization owners.
- Platform admin: list organizations, suspend/reactivate. No access to tenant financial data.

## Non-functional requirements

- Money stored as integer rupiah; no floats.
- All tenant-owned tables carry `organization_id` and are filtered by a global scope.
- Every write validated server-side; file uploads restricted by mime and size.
- Responsive layout, dark mode, keyboard-accessible forms, skip link, labelled controls.
- Indonesian UI copy.

## Explicitly not implemented

- Subscription billing and plan enforcement (`organizations.plan` is descriptive only).
- Live payment gateway / WhatsApp credentials (adapters exist, activation blocked).
- Two-factor authentication.
- Multi-database or schema-per-tenant isolation.
- Automated backups to off-site object storage.
