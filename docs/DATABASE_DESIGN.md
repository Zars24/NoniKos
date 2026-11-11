# NoniKos — Database Design

**Status:** implemented by the migrations listed below. MariaDB 10.4, `utf8mb4_unicode_ci`, InnoDB.

Conventions: `id` bigint unsigned PK, `organization_id` FK on every tenant-owned table, `timestamps()`, money = `bigInteger` (rupiah), dates = `date`, soft deletion only where history must survive.

| Migration | Tables |
|---|---|
| `..._create_users_table` (Laravel) + `..._add_profile_fields_to_users_table` | users (+ is_platform_admin, status, theme_preference) |
| `..._create_organizations_tables` | organizations, organization_memberships, membership_properties |
| `..._create_property_tables` | properties, buildings, room_types, rooms, room_photos, room_status_histories |
| `..._create_tenant_tables` | tenants, tenant_documents, bookings, rental_agreements |
| `..._create_billing_tables` | invoices, invoice_items, payments, payment_links, payment_webhook_events, financial_adjustments |
| `..._create_operations_tables` | expense_categories, expenses, maintenance_requests, maintenance_comments, message_templates, notification_deliveries, activity_logs, backup_records |

## Key constraints

- `organization_memberships`: unique `(organization_id, user_id)`; index `(user_id, status)`.
- `membership_properties`: unique `(membership_id, property_id)`; cascade on membership delete.
- `rooms`: unique `(property_id, number)`; index `(organization_id, status)`; index `(property_id, status)`.
- `rental_agreements`: index `(room_id, status, start_date, end_date)`; `booking_id` unique nullable.
- `invoices`: unique `number`, unique `(rental_agreement_id, period_start)`; index `(organization_id, status, due_date)`; `payments` restrict on delete.
- `payments`: unique `reference` nullable; `invoices` restrict on delete.
- `payment_webhook_events`: unique `(provider, event_id)`.
- `activity_logs`: no `updated_at`, append-only by policy; index `(organization_id, created_at)`.
- `backup_records`: filename unique per organization.

## Deletion / archival

| Table | Rule |
|---|---|
| organizations | never deleted via UI; deactivate |
| properties, rooms | `is_active=false` / status `unavailable` when history exists |
| tenants | status `inactive`; delete only when no agreements/invoices/payments |
| rental_agreements | status `terminated`/`expired`, never deleted |
| invoices, payments, adjustments, expenses | no delete route; reversal instead |
| invoice_items, comments, photos, status histories | cascade with parent |
| activity_logs, backup_records | no delete route |

## Indexing rationale

Queries are all org-scoped, so composite indexes lead with `organization_id`. Billing runs filter `status` + `period_start`; dashboards filter `status` + `due_date`; room availability filters `property_id` + `status`.

## Money representation

`bigInteger` rupiah. No decimals needed because IDR minor unit is not used in practice; documented decision. A future currency switch requires minor-unit storage.
