# NoniKos — Architecture

**Status:** implemented (Phase 0–2 baseline). Update when structure changes.

## Runtime (audited 2026-10-19)

| Item | Actual |
|---|---|
| PHP | 8.1.10 (XAMPP, `C:\xampp\php`) |
| Composer | 2.5.8 |
| Laravel | 10.50.3 |
| Node / npm | 20.17.0 / 11.6.0 |
| Database | MariaDB 10.4.25 (`127.0.0.1:3306`, database `nonikos`) |
| Frontend | Blade + Tailwind CSS 3 + Alpine.js 3, Vite 4 |
| Tests | PHPUnit 10 (SQLite in-memory) |
| Git | not initialized in workspace at audit time |

Laravel 11/12 requires PHP >= 8.2, so Laravel 10 is the newest framework compatible with the installed PHP. Do not upgrade majors without upgrading PHP first.

## Style

Modular monolith, conventional Laravel layout. Business rules live in `app/Services`, authorization in `app/Policies` + `app/Support/Permissions.php`, validation in `app/Http/Requests`, integrations behind interfaces in `app/Services/Integrations` bound in `AppServiceProvider`.

```
app/
  Console/Commands/          billing, reminders, backups, scheduler entry points
  Enums/                     status enums (Room, Invoice, Payment, ...)
  Http/
    Controllers/             thin controllers (Auth, Admin, Portal, Webhooks)
    Middleware/              EnsureOrganization, EnsurePlatformAdmin, SetLocale
    Requests/                server-side validation
  Models/                    Eloquent models + BelongsToOrganization trait
  Policies/                  per-entity authorization
  Services/
    Billing/                 invoice generation, numbering, late fees
    Payments/                recording, allocation, refunds
    Integrations/            PaymentGateway, WhatsAppSender + implementations
    Reports/                 finance aggregation
    Backups/                 database dump + metadata
  Support/
    TenantContext.php        current organization singleton
    Permissions.php          permission catalogue + role matrix
resources/views/
  layouts/app.blade.php      authenticated shell (sidebar/navbar)
  layouts/guest.blade.php    auth shell
  components/ui/*            design-system components
```

## Multi-tenancy

Single database, shared schema.

- Every tenant-owned table has `organization_id` with a foreign key and an index.
- `App\Models\Concerns\BelongsToOrganization` adds:
  - a global scope filtering by `TenantContext::id()` when a context is set,
  - automatic `organization_id` fill on create,
  - a guard that throws if a model is saved without an organization.
- `TenantContext` is set by `EnsureOrganization` middleware from the authenticated user's **active membership**, never from a browser-supplied organization id. A user with several memberships switches org through `POST /organization/switch` which validates membership and writes the id into the session.
- Console commands and queued jobs iterate organizations and call `TenantContext::run($orgId, fn () => ...)`.
- Cross-tenant access is additionally blocked by policies that check `organization_id` equality; tests cover ID tampering on URLs.

Platform Super Admin (`users.is_platform_admin`) is a separate axis: it can access `/platform` only, is never auto-attached to tenant data, and every action is written to `activity_logs`.

## Authorization

- Roles are stored on `organization_memberships.role` (`owner`, `manager`, `finance`, `operations`, `tenant`).
- Permissions are a fixed catalogue in `App\Support\Permissions`; the role→permission matrix is in the same class, so it is versioned with code and covered by tests.
- `User::hasPermission($permission)` resolves the active membership; `Gate::before` grants platform admins only `platform.*` abilities.
- Policies call `$user->hasPermission(...)`; routes also use `can:` middleware where useful. Hiding UI is never the only control.
- Staff property assignment is stored in `membership_properties`; `User::canAccessProperty()` restricts managers/operations staff when assignments exist.

## Money and time

- All money is stored as **integer rupiah** (`BIGINT`), never floats. Input accepts `1.500.000` and is normalised by `App\Support\Money`.
- Dates use `Asia/Jakarta` by default (`config/app.php`); billing periods are computed on calendar dates, not timestamps.
- Financial writes run inside `DB::transaction` with row locks where balances are recomputed.

## Frontend

Server-rendered Blade, Alpine only for local UI state (menus, modals, theme toggle). No SPA. Theme preference is stored in `localStorage` with a `system` option and applied before paint to avoid flash. Tailwind design tokens mirror the palette in `docs/DESIGN_SYSTEM.md`.

## Known architectural limits

- Single-database tenancy cannot enforce isolation at the database level; isolation relies on the global scope plus policies plus tests. A schema-per-tenant or RLS database would be the upgrade path.
- `laravel/framework` 10.50.3 is affected by advisories fixed only in 12.60+ (CRLF e-mail rule, signed-URL path confusion). Mitigations: e-mail input normalised/rejected when it contains CR/LF, no signed URLs used for sensitive files. Upgrade path: PHP 8.2+ then Laravel 12.
- Backups are whole-database dumps, so backup/restore lives in the platform-admin area (`/platform/backups`) and is gated by `users.is_platform_admin`; organization roles have no access. The UI never accepts SQL uploads.
