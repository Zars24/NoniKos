<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\RentalAgreement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingService
{
    public function __construct(
        protected InvoiceNumberGenerator $numbers,
        protected ActivityLogger $activity,
    ) {
    }

    /**
     * Generate (or return) the invoice for one billing period of an agreement.
     * Idempotent: unique (rental_agreement_id, period_start) + firstOrCreate inside a transaction.
     */
    public function generateForPeriod(RentalAgreement $agreement, Carbon $periodStart): Invoice
    {
        $periodStart = $periodStart->copy()->startOfDay();
        $organization = Organization::findOrFail($agreement->organization_id);

        return DB::transaction(function () use ($agreement, $periodStart, $organization) {
            // Lock the organization row to serialise invoice numbering per organization.
            Organization::whereKey($organization->id)->lockForUpdate()->first();

            $existing = Invoice::withoutGlobalScopes()
                ->where('rental_agreement_id', $agreement->id)
                ->whereDate('period_start', $periodStart)
                ->first();

            if ($existing) {
                return $existing;
            }

            $cycle = max(1, $agreement->billing_cycle_months);
            $periodEnd = $periodStart->copy()->addMonthsNoOverflow($cycle)->subDay();
            $dueDate = $periodStart->copy()->addDays($agreement->due_days);

            $isFirstPeriod = $agreement->start_date->isSameDay($periodStart);
            $amount = $isFirstPeriod
                ? $this->proratedAmount($agreement, $periodStart, $periodEnd)
                : (int) $agreement->monthly_price * $cycle;

            $invoice = Invoice::create([
                'organization_id' => $agreement->organization_id,
                'property_id' => $agreement->property_id,
                'tenant_id' => $agreement->tenant_id,
                'rental_agreement_id' => $agreement->id,
                'number' => $this->numbers->next($organization, $periodStart),
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'issue_date' => $periodStart,
                'due_date' => $dueDate,
                'subtotal' => $amount,
                'total' => $amount,
                'status' => 'draft',
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'type' => 'rent',
                'description' => sprintf(
                    'Sewa kamar %s — %s s/d %s',
                    $agreement->room?->number ?? '-',
                    $periodStart->translatedFormat('d M Y'),
                    $periodEnd->translatedFormat('d M Y')
                ),
                'quantity' => $cycle,
                'unit_price' => (int) $agreement->monthly_price,
                'amount' => $amount,
            ]);

            return $invoice->fresh(['items']);
        });
    }

    /**
     * First period prorated by remaining days in the start month.
     * ponytail: day-count prorating only; add anniversary-based prorating if partial cycles appear.
     */
    public function proratedAmount(RentalAgreement $agreement, Carbon $start, Carbon $end): int
    {
        $daysInMonth = $start->daysInMonth;
        $daysUsed = $start->diffInDays($end) + 1;

        if ($daysUsed >= $daysInMonth) {
            return (int) $agreement->monthly_price;
        }

        return (int) round($agreement->monthly_price * $daysUsed / $daysInMonth);
    }

    /**
     * Billing run for a month: creates every missing invoice for active agreements.
     * Safe to re-run; returns counts for the command output.
     */
    public function runForMonth(?int $organizationId, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $created = 0;
        $skipped = 0;

        $query = RentalAgreement::withoutGlobalScopes()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $start->copy()->endOfMonth())
            ->whereDate('end_date', '>=', $start)
            ->with(['room']);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        $query->chunkById(200, function ($agreements) use ($start, &$created, &$skipped) {
            foreach ($agreements as $agreement) {
                \App\Support\TenantContext::run($agreement->organization_id, function () use ($agreement, $start, &$created, &$skipped) {
                    $periodStart = $start->copy();
                    // Align to the agreement's billing day for the first cycle.
                    if ($agreement->start_date->gt($start)) {
                        $periodStart = $agreement->start_date->copy()->startOfDay();
                    }

                    $exists = Invoice::where('rental_agreement_id', $agreement->id)
                        ->whereDate('period_start', $periodStart)->exists();

                    if ($exists) {
                        $skipped++;
                        return;
                    }

                    $this->generateForPeriod($agreement, $periodStart);
                    $created++;
                });
            }
        });

        return ['created' => $created, 'skipped' => $skipped];
    }

    public function issue(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages(['invoice' => 'Invoice ini sudah diterbitkan.']);
        }

        $invoice->forceFill([
            'status' => 'issued',
            'issued_at' => now(),
            'issue_date' => today(),
        ])->save();

        $this->activity->log('invoice.issued', $invoice, 'Invoice '.$invoice->number.' diterbitkan', ['total' => $invoice->total]);

        return $invoice;
    }

    /**
     * Apply the configured late fee once, after the due date. Idempotent via late_fee_applied_at.
     */
    public function applyLateFee(Invoice $invoice, ?int $percent = null): ?Invoice
    {
        if ($invoice->late_fee_applied_at || $invoice->balance() <= 0 || $invoice->status === 'cancelled') {
            return null;
        }

        if ($invoice->due_date->isFuture()) {
            return null;
        }

        $organization = Organization::findOrFail($invoice->organization_id);
        $rate = $percent ?? $organization->late_fee_percent;

        if ($rate <= 0) {
            return null;
        }

        return DB::transaction(function () use ($invoice, $rate) {
            $fee = (int) round($invoice->total * $rate / 100);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'type' => 'late_fee',
                'description' => "Denda keterlambatan {$rate}%",
                'quantity' => 1,
                'unit_price' => $fee,
                'amount' => $fee,
            ]);

            $invoice->forceFill([
                'late_fee_total' => $invoice->late_fee_total + $fee,
                'total' => $invoice->total + $fee,
                'late_fee_applied_at' => now(),
            ]);
            $invoice->recalculateStatus();
            $invoice->save();

            $this->activity->log('invoice.late_fee', $invoice, 'Denda keterlambatan diterapkan pada '.$invoice->number, ['fee' => $fee]);

            return $invoice;
        });
    }
}
