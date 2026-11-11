<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Financial summary for a period. Definitions are intentionally explicit:
     * billed  = invoice total issued in period (draft/cancelled excluded)
     * received= confirmed payments in period
     * outstanding = current balance of invoices whose period overlaps the range
     * expenses= recorded operating expenses in period
     * cash_flow = received - expenses  (NOT profit)
     */
    public function summary(Carbon $from, Carbon $to, ?int $propertyId = null): array
    {
        $invoiceBase = Invoice::query()
            ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()])
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId));

        $billed = (clone $invoiceBase)->sum('total');
        $outstanding = (clone $invoiceBase)->get()->sum(fn (Invoice $i) => $i->balance());
        $overdue = (clone $invoiceBase)->where('due_date', '<', today())->whereNotIn('status', ['paid', 'cancelled'])->get()
            ->sum(fn (Invoice $i) => $i->balance());

        $paymentQuery = Payment::query()
            ->where('status', 'confirmed')
            ->whereBetween('paid_at', [$from->toDateString(), $to->toDateString()])
            ->when($propertyId, fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('property_id', $propertyId)));

        $received = (clone $paymentQuery)->sum('amount');

        $expenses = Expense::query()
            ->whereBetween('spent_on', [$from->toDateString(), $to->toDateString()])
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            ->sum('amount');

        return [
            'billed' => (int) $billed,
            'received' => (int) $received,
            'outstanding' => (int) $outstanding,
            'overdue' => (int) $overdue,
            'expenses' => (int) $expenses,
            'cash_flow' => (int) $received - (int) $expenses,
        ];
    }

    /**
     * Monthly series for charting (billed vs received).
     */
    public function monthlySeries(Carbon $from, Carbon $to, ?int $propertyId = null): array
    {
        $months = [];
        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lte($to)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();

            $billed = Invoice::query()
                ->whereBetween('period_start', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->whereNotIn('status', ['draft', 'cancelled'])
                ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
                ->sum('total');

            $received = Payment::query()
                ->where('status', 'confirmed')
                ->whereBetween('paid_at', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->when($propertyId, fn ($q) => $q->whereHas('invoice', fn ($i) => $i->where('property_id', $propertyId)))
                ->sum('amount');

            $months[] = [
                'label' => $monthStart->translatedFormat('M Y'),
                'billed' => (int) $billed,
                'received' => (int) $received,
            ];

            $cursor->addMonth();
        }

        return $months;
    }

    /**
     * Invoice aging buckets based on days past due.
     */
    public function aging(?int $propertyId = null): array
    {
        $buckets = ['current' => 0, '1-30' => 0, '31-60' => 0, '61-90' => 0, '>90' => 0];

        Invoice::query()
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            ->get()
            ->each(function (Invoice $invoice) use (&$buckets) {
                $days = $invoice->due_date->isFuture() ? 0 : $invoice->due_date->diffInDays(today());
                $bucket = match (true) {
                    $days <= 0 => 'current',
                    $days <= 30 => '1-30',
                    $days <= 60 => '31-60',
                    $days <= 90 => '61-90',
                    default => '>90',
                };
                $buckets[$bucket] += $invoice->balance();
            });

        return $buckets;
    }

    public function exportRows(Carbon $from, Carbon $to, ?int $propertyId = null): array
    {
        return Invoice::query()
            ->with(['tenant:id,full_name', 'property:id,name'])
            ->whereBetween('period_start', [$from->toDateString(), $to->toDateString()])
            ->when($propertyId, fn ($q) => $q->where('property_id', $propertyId))
            ->orderBy('period_start')
            ->get()
            ->map(fn (Invoice $invoice) => [
                'Nomor Invoice' => $invoice->number,
                'Penyewa' => $invoice->tenant?->full_name,
                'Properti' => $invoice->property?->name,
                'Periode Mulai' => $invoice->period_start->toDateString(),
                'Jatuh Tempo' => $invoice->due_date->toDateString(),
                'Total' => $invoice->total,
                'Dibayar' => $invoice->amount_paid,
                'Sisa' => $invoice->balance(),
                'Status' => $invoice->status,
            ])->all();
    }
}
