# NoniKos — Permissions

**Status:** implemented in `app/Support/Permissions.php`, enforced by policies and `can:` middleware.

## Roles (`organization_memberships.role`)

| Role | Scope |
|---|---|
| `owner` | full organization |
| `manager` | operations + finance view, assigned properties |
| `finance` | billing, payments, finance reports/exports |
| `operations` | rooms, tenants, contracts, maintenance, assigned properties |
| `tenant` | own profile, agreements, invoices, payments, own maintenance |

Platform Super Admin is **not** a membership role; it is `users.is_platform_admin` and only grants `platform.*` abilities.

## Permission catalogue

```
dashboard.view
properties.view        properties.create   properties.update   properties.delete
rooms.view             rooms.manage
tenants.view           tenants.manage      tenants.import
contracts.view         contracts.manage
invoices.view          invoices.create     invoices.adjust     invoices.cancel
payments.view          payments.record     payments.refund
finance.reports.view   finance.expenses.manage   finance.exports.create
maintenance.view       maintenance.manage
staff.view             staff.manage
activity_logs.view
settings.view          settings.manage
portal.access
platform.access
```

Database backup and restore are **not** organization permissions: a dump contains every
organization's rows, so those routes live under `platform.admin` and are gated by
`users.is_platform_admin`, not by a role in an organization.

## Matrix

| Permission | owner | manager | finance | operations | tenant |
|---|---|---|---|---|---|
| dashboard.view | ✓ | ✓ | ✓ | ✓ | |
| properties.view | ✓ | ✓ | ✓ | ✓ | |
| properties.create/update/delete | ✓ | | | | |
| rooms.view | ✓ | ✓ | ✓ | ✓ | |
| rooms.manage | ✓ | ✓ | | ✓ | |
| tenants.view | ✓ | ✓ | ✓ | ✓ | |
| tenants.manage | ✓ | ✓ | | ✓ | |
| tenants.import | ✓ | ✓ | | | |
| contracts.view | ✓ | ✓ | ✓ | ✓ | |
| contracts.manage | ✓ | ✓ | | ✓ | |
| invoices.view | ✓ | ✓ | ✓ | | ✓ (own) |
| invoices.create / adjust / cancel | ✓ | | ✓ | | |
| payments.view | ✓ | ✓ | ✓ | | ✓ (own) |
| payments.record | ✓ | | ✓ | | |
| payments.refund | ✓ | | ✓ | | |
| finance.reports.view | ✓ | ✓ | ✓ | | |
| finance.expenses.manage | ✓ | | ✓ | | |
| finance.exports.create | ✓ | | ✓ | | |
| maintenance.view | ✓ | ✓ | | ✓ | ✓ (own) |
| maintenance.manage | ✓ | ✓ | | ✓ | |
| staff.view / staff.manage | ✓ | | | | |
| activity_logs.view | ✓ | | | | |
| settings.view | ✓ | ✓ | ✓ | ✓ | |
| settings.manage | ✓ | | | | |
| portal.access | | | | | ✓ |

## Enforcement rules

1. Every route with tenant data sits behind `auth`, `verified`, `organization` middleware.
2. Controllers/policies additionally assert `model.organization_id === TenantContext::id()`.
3. Tenant-role users are routed to `/portal`; tenant policies compare `tenant.user_id` to `auth()->id()`.
4. Exports, PDF downloads, webhook endpoints and AJAX endpoints use the same policy checks as pages.
5. Hidden UI is a convenience only; every mutation is authorised server-side and covered by a test.
6. Permission changes and staff deactivation are written to `activity_logs`.
