<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Property;
use App\Models\RentalAgreement;
use App\Services\ActivityLogger;
use App\Services\BillingService;
use App\Services\NotificationService;
use App\Services\PaymentService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $invoices = Invoice::query()
            ->with(['tenant:id,full_name', 'property:id,name'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('q'), fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('number', 'like', "%{$term}%")
                    ->orWhereHas('tenant', fn ($t) => $t->where('full_name', 'like', "%{$term}%"));
            }))
            ->when($request->query('from'), fn ($q, $from) => $q->whereDate('period_start', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->whereDate('period_start', '<=', $to))
            ->latest('period_start')->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        $this->authorize('create', Invoice::class);

        return view('invoices.create', [
            'agreements' => RentalAgreement::with(['tenant:id,full_name', 'room:id,number'])
                ->where('status', 'active')->orderBy('code')->get(),
            'properties' => Property::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, BillingService $billing)
    {
        $this->authorize('create', Invoice::class);

        $data = $request->validate([
            'rental_agreement_id' => ['required', 'integer', Rule::exists('rental_agreements', 'id')->where('organization_id', $request->user()->activeMembership()->organization_id)],
            'period_start' => ['required', 'date'],
        ]);

        $agreement = RentalAgreement::findOrFail($data['rental_agreement_id']);
        $invoice = $billing->generateForPeriod($agreement, \Illuminate\Support\Carbon::parse($data['period_start']));

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice '.$invoice->number.' siap.');
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['items', 'payments.recordedBy:id,name', 'tenant', 'property', 'agreement.room', 'paymentLinks']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('adjust', $invoice);

        abort_unless($invoice->status === 'draft', 403, 'Hanya invoice draf yang dapat disunting.');

        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('adjust', $invoice);

        abort_unless($invoice->status === 'draft', 403, 'Hanya invoice draf yang dapat disunting.');

        $data = $request->validate([
            'discount_total' => ['nullable', 'string', 'max:20'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($invoice, $data) {
            $discount = Money::parse($data['discount_total'] ?? 0);

            if ($discount > $invoice->subtotal) {
                $discount = $invoice->subtotal;
            }

            $invoice->forceFill([
                'discount_total' => $discount,
                'total' => max(0, $invoice->subtotal - $discount + $invoice->late_fee_total),
                'due_date' => $data['due_date'],
                'notes' => $data['notes'] ?? null,
            ])->save();
        });

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice diperbarui.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        abort(403, 'Invoice tidak dapat dihapus; gunakan pembatalan.');
    }

    public function issue(Invoice $invoice, BillingService $billing)
    {
        $this->authorize('adjust', $invoice);

        $billing->issue($invoice);

        return back()->with('status', 'Invoice diterbitkan.');
    }

    public function cancel(Invoice $invoice)
    {
        $this->authorize('cancel', $invoice);

        abort_if($invoice->amount_paid > 0, 422, 'Invoice yang sudah memiliki pembayaran tidak dapat dibatalkan tanpa pembalikan pembayaran.');
        abort_if($invoice->status === 'cancelled', 422, 'Invoice sudah dibatalkan.');

        $invoice->forceFill(['status' => 'cancelled'])->save();

        app(ActivityLogger::class)->log('invoice.cancelled', $invoice, 'Invoice '.$invoice->number.' dibatalkan');

        return back()->with('status', 'Invoice dibatalkan.');
    }

    public function applyLateFee(Invoice $invoice, BillingService $billing)
    {
        $this->authorize('adjust', $invoice);

        $updated = $billing->applyLateFee($invoice);

        return back()->with('status', $updated ? 'Denda keterlambatan diterapkan.' : 'Denda tidak dapat diterapkan (belum jatuh tempo, sudah diterapkan, atau tarif 0).');
    }

    public function storeAdjustment(Request $request, Invoice $invoice)
    {
        $this->authorize('adjust', $invoice);

        $data = $request->validate([
            'type' => ['required', Rule::in(\App\Models\FinancialAdjustment::TYPES)],
            'amount' => ['required', 'string', 'max:20'],
            'reason' => ['required', 'string', 'max:200'],
        ]);

        abort_if($invoice->amount_paid > 0 && $data['type'] === 'invoice_correction', 422, 'Koreksi nilai invoice yang sudah dibayar harus melalui pembalikan pembayaran.');

        $amount = Money::parse($data['amount']);

        DB::transaction(function () use ($invoice, $data, $amount) {
            \App\Models\FinancialAdjustment::create([
                'organization_id' => $invoice->organization_id,
                'invoice_id' => $invoice->id,
                'tenant_id' => $invoice->tenant_id,
                'type' => $data['type'],
                'amount' => $amount,
                'reason' => $data['reason'],
                'created_by' => auth()->id(),
            ]);

            if ($data['type'] === 'invoice_correction') {
                $invoice->forceFill([
                    'discount_total' => $invoice->discount_total + $amount,
                    'total' => max(0, $invoice->total - $amount),
                ])->save();
                $invoice->recalculateStatus();
                $invoice->save();
            }
        });

        app(ActivityLogger::class)->log('invoice.adjusted', $invoice, 'Penyesuaian invoice '.$invoice->number, [
            'type' => $data['type'],
            'amount' => $amount,
            'reason' => $data['reason'],
        ]);

        return back()->with('status', 'Penyesuaian dicatat.');
    }

    public function pdf(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['items', 'tenant', 'property', 'agreement.room', 'organization']);

        return Pdf::loadView('pdf.invoice', compact('invoice'))
            ->setPaper('a4')
            ->download('invoice-'.str_replace('/', '-', $invoice->number).'.pdf');
    }

    public function sendReminder(Invoice $invoice, NotificationService $notifications)
    {
        $this->authorize('view', $invoice);

        if (! $invoice->tenant?->phone) {
            return back()->with('error', 'Penyewa belum memiliki nomor telepon.');
        }

        $delivery = $notifications->sendTemplate(
            $invoice->isOverdue() ? 'overdue_reminder' : 'payment_reminder',
            $invoice->tenant->phone,
            $notifications->invoiceVariables($invoice),
            $invoice,
            $invoice->tenant
        );

        return back()->with(
            $delivery->status === 'sent' ? 'status' : 'error',
            $delivery->status === 'sent'
                ? 'Pesan terkirim melalui provider WhatsApp.'
                : 'Pesan masuk antrean tetapi tidak terkirim: '.$delivery->error
        );
    }
}
