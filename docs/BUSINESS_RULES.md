# NoniKos — Business Rules

**Status:** implemented rules marked ✓; rules still missing marked ✗.

## Money

1. ✓ All amounts are integer rupiah. No floats in DB or PHP arithmetic.
2. ✓ Invoice `total = subtotal - discount + late_fee`; `amount_paid` is derived from confirmed payments; `balance = total - amount_paid`.
3. ✓ Status derivation: `paid` when balance ≤ 0 and total > 0; `partially_paid` when 0 < paid < total; `overdue` when unpaid and `due_date < today`; `cancelled` and `draft` are explicit states.
4. ✓ Payments allocate to a single invoice; allocation cannot exceed the invoice balance (overpayment is rejected, not silently credited).
5. ✓ Reversal/refund keeps the original payment row, sets status `reversed`, and recomputes the invoice. No hard delete of confirmed money.
6. ✓ Late fee is a per-organization percentage applied once per invoice after the due date by a scheduled command; re-running does not stack fees (guarded by `late_fee_applied_at`).

## Invoices

7. ✓ Number format `INV/{org-slug}/{YYYYMM}/{sequence}` with a DB unique index; sequence generated inside the same transaction with `lockForUpdate` on the org row.
8. ✓ Recurring billing keyed by `(rental_agreement_id, period_start)`; a unique index plus an existence check inside the same transaction make re-runs idempotent (returns the existing invoice).
9. ✓ Issued invoices snapshot item descriptions and prices; editing a room or agreement never mutates them.
10. ✓ Invoice with payments cannot be deleted or cancelled without reversal; corrections use `financial_adjustments`.
11. ✓ Prorating: first period is prorated by days when `start_date` is not the 1st (daily rate = monthly / days in that month), rounded to whole rupiah.
11.1 ✓ Business codes (`TNT-`, `AGR-`, `BKG-`) are globally unique across organizations; `CodeGenerator` counts globally to match the DB unique index.

## Contracts and rooms

12. ✓ One active agreement per room. Overlap check on `[start_date, end_date]` inside a transaction with `lockForUpdate`; DB index supports the query.
13. ✓ Active agreement ⇒ room status `occupied`. Checkout/termination ⇒ `available`. Every transition is recorded in `room_status_histories`.
14. ✓ Booking has `pending`, `confirmed`, `converted`, `cancelled`, `expired`. Converting creates the agreement once (idempotent by booking id); only `pending` bookings can be deleted.
15. ✓ Agreement renewal extends `end_date` and rejects a date that is not later than the current end date. The room stays occupied by the same tenant.
16. ✓ Room transfer closes the old agreement (reason `transfer`) and opens a new one, preserving history.
17. ✓ Contract price/deposit are snapshots; changing `rooms.monthly_price` affects only new agreements.

## Tenants

18. ✓ Tenants are scoped to an organization; a tenant user account links via `tenants.user_id` (unique).
19. ✓ Tenants with financial history are deactivated, never deleted.
20. ✓ Tenant portal queries are filtered by the authenticated user's tenant id, not by URL ids.

## Payments and online gateway

21. ✓ Manual payment records require amount, method, paid_at; confirmation recomputes the invoice.
22. ✓ Online payment link amounts and currency are verified against the invoice before the webhook is accepted; signature verified with the provider secret; events deduplicated by `event_id`.
23. ✓ Opening a success page never marks anything paid — only a verified webhook/callback does.

## Reports

24. ✓ "Billed" (invoiced) ≠ "Received" (confirmed payments) ≠ "Outstanding" (balance) ≠ "Cash flow" (received − expenses). Labels state which is which.
25. ✓ Occupancy = occupied rooms / (available + occupied + reserved + maintenance) for the selected properties; `unavailable` rooms are excluded.
26. ✓ Report filters (period, property) apply to every query and export.

## Operations

27. ✓ Activity log is append-only; no update/delete routes.
28. ✓ Backups are created by a CLI command/scheduler, stored on a private disk, checksummed; restore requires a confirmation token and writes a pre-restore backup.
29. ✓ Staff deactivation sets membership status `inactive` and deletes the user's sessions.
30. ✓ Scheduler jobs are idempotent and log failures to `activity_logs`/`notification_deliveries`.

## Time

31. ✓ Default timezone `Asia/Jakarta`; billing period dates are calendar dates (`date` casts), due date = period start + configured `due_days` (default 7).
