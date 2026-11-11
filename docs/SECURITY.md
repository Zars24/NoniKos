# NoniKos — Security

**Status:** controls implemented; residual risks listed at the end. This is not a claim of complete security.

## Controls

| Area | Implementation |
|---|---|
| Authentication | Laravel session auth (Breeze), bcrypt, e-mail verification optional per env, login throttling (`5/min` per email+IP via `LoginRequest`), session regeneration on login |
| Password reset | signed token, 60 min expiry, throttled |
| Authorization | policies + permission catalogue, `can:` middleware, org equality checks in every policy |
| Tenant isolation | global scope via `BelongsToOrganization` + `TenantContext` set from membership, never from request input; cross-org tests |
| CSRF | Laravel middleware on all web routes |
| XSS | Blade escaping; no `{!! !!}` on user data |
| SQL injection | Eloquent/query builder bindings; no raw SQL with interpolation |
| CSV/XLSX injection | export cells starting with `= + - @` are prefixed with `'` so spreadsheet apps treat them as text |
| Mass assignment | explicit `$fillable` on every model |
| File uploads | validated mime + size, stored on `private` disk, served through authorised controller routes only |
| Private storage | `storage/app/private` for proofs, documents, backups |
| Rate limiting | login (`5/min` per email+IP plus `20/min` route throttle), registration/forgot/reset/confirm-password (`6/min`), payment webhooks (`120/min`), `/health` (`60/min`) |
| Webhooks | HMAC-SHA256 signature verification over the raw body, fails closed when no secret is configured, `event_id` idempotency, amount/currency verification |
| Secrets | `.env` only; `.env` git-ignored; `docs` never contains values |
| Session | `SESSION_SECURE_COOKIE` + `SameSite=lax` in production, `http_only` on |
| Deactivated staff | membership status `inactive` + session invalidation on deactivation |
| Audit | append-only `activity_logs` for auth, money, permission, export, backup events |
| Backups | whole-database dumps, platform-admin only (a dump holds every organization's rows); private disk, sha256 checksum, restore requires confirmation phrase + password + pre-restore snapshot |
| Exceptions | `APP_DEBUG=false` in production; generic error pages |

## Tested controls

Covered by automated tests in `tests/Feature`:

- cross-organization access on properties, rooms, tenants, agreements, invoices, payments (403/404)
- tenant portal cannot read another tenant's invoice or PDF by URL id
- tenant-role users cannot search the organization's tenant list
- role permission matrix (finance cannot manage staff, operations cannot record payments, etc.)
- deactivated user is denied at middleware level
- invoice paid state cannot be forged by visiting a success page
- duplicate webhook event does not double-pay; bad signature and amount mismatch are rejected
- unauthenticated access redirects to login
- private room photos and tenant documents require authorization and correct parent record
- backup restore requires the confirmation phrase and rejects a tampered file
- organization staff cannot list, download, or create whole-database backups
- activity log is scoped to the organization and its own members' auth events
- CSV export neutralises spreadsheet formula injection

## Residual risks

1. **Laravel 10.50.3 advisories** (CRLF injection in default e-mail rule, signed-URL path confusion). Mitigation: e-mail inputs are normalised and CR/LF rejected; no signed URLs for sensitive downloads. Real fix requires PHP ≥ 8.2 + Laravel 12.
2. **Shared-schema tenancy** — a bug that bypasses the global scope would leak data. Mitigated by policy checks and tests, not by the database.
3. **Webhook replay** — mitigated by unique `event_id` and signature check. There is no timestamp tolerance, so an old valid payload with a new event id would be accepted; add a timestamp/nonce when a real provider is connected.
4. **Backup encryption at rest** depends on the storage layer; files are private but not encrypted by the app.
5. **No 2FA** for owners/platform admins yet.
6. **Payment gateway and WhatsApp** adapters are tested against a local sandbox implementation, not a live provider.
7. **No malware scanning** on uploaded files; mime/size validation only.
8. **Rate limiting** is per-process cache; a multi-node deployment needs a shared cache driver.
