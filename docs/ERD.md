# NoniKos — Entity Relationship Design

**Status:** implemented in `database/migrations`. Keep in sync.

## Identity & tenancy

```
users 1─n organization_memberships n─1 organizations
organizations 1─n properties 1─n buildings
organization_memberships n─n properties   (membership_properties)
users 0..1─1 tenants
```

- `users.email` unique. `users.is_platform_admin` boolean, `status` active/inactive.
- `organization_memberships` unique `(organization_id, user_id)`, role + status.
- `membership_properties` unique `(membership_id, property_id)`.

## Property & rooms

```
properties 1─n rooms n─1 room_types
properties 1─n buildings 1─n rooms
rooms 1─n room_status_histories
```

- `rooms` unique `(property_id, number)`, status enum.
- Room photo path stored on `rooms.photo_path` (single primary photo) plus `room_photos` for gallery.
- `room_types` holds default price/deposit/billing period; a room snapshots its own `monthly_price` and `deposit_amount`.

## Tenants, bookings, contracts

```
tenants 1─n bookings n─1 rooms
tenants 1─n rental_agreements n─1 rooms
bookings 0..1─1 rental_agreements
rental_agreements 1─n invoices
rental_agreements 1─n financial_adjustments
```

- `bookings.code`, `rental_agreements.code` unique.
- One active agreement per room at a time; overlap enforced in `ContractService` inside a transaction with `lockForUpdate`, plus a DB index on `(room_id, status, start_date, end_date)`.
- Price fields are snapshots; editing a room price never rewrites an agreement or an issued invoice.

## Billing & payments

```
invoices 1─n invoice_items
invoices 1─n payments
invoices 1─n payment_links
invoices 1─n financial_adjustments
payments n─1 tenants
```

- `invoices` unique `(rental_agreement_id, period_start)` — idempotency key for recurring billing.
- `invoices.number` unique; `payments.reference` unique when present.
- `invoice_items.type` ∈ rent, deposit, late_fee, discount, other.
- `payments.status` ∈ pending, confirmed, reversed. Reversal keeps the row and its history.
- `payment_webhook_events.event_id` unique — duplicate webhook protection.
- `financial_adjustments` records corrections and deposit refunds (credit/debit) with actor and reason.

## Operations

```
maintenance_requests 1─n maintenance_comments
properties 1─n expenses n─1 expense_categories
organizations 1─n message_templates
organizations 1─n notification_deliveries
organizations 1─n activity_logs
organizations 1─n backup_records
```

- `activity_logs` is append-only (no update/delete route, no `updated_at`).
- `notification_deliveries.status` ∈ queued, sent, failed; `attempts` and `error` retained.
- `backup_records` stores filename, disk, size, sha256 checksum, status.

## Deletion policy

- Financial rows (`invoices`, `payments`, `financial_adjustments`, `expenses`, `activity_logs`, `backup_records`) are never cascade-deleted. Parents use `restrictOnDelete` where a cascade would destroy history.
- Tenants with financial history cannot be deleted; they are deactivated.
- Rooms/properties with agreements are deactivated rather than deleted.
- Operational children (invoice items, comments, status histories, membership property links) cascade with their parent.

## Diagram (text)

```
users ─┬─ organization_memberships ─ organizations ─ properties ─┬─ buildings
       │                                  │                     ├─ room_types
       │                                  │                     └─ rooms ─ room_status_histories
       │                                  ├─ tenants ─┬─ bookings ─┐
       │                                  │           └─ rental_agreements ─ invoices ─ invoice_items
       │                                  │                                  ├─ payments
       │                                  │                                  ├─ payment_links
       │                                  │                                  └─ financial_adjustments
       │                                  ├─ expenses ─ expense_categories
       │                                  ├─ maintenance_requests ─ comments
       │                                  ├─ message_templates
       │                                  ├─ notification_deliveries
       │                                  ├─ activity_logs
       │                                  └─ backup_records
       └─ (tenant user) tenants.user_id
```
