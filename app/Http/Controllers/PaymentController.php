<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with(['tenant:id,full_name', 'invoice:id,number', 'recordedBy:id,name'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('method'), fn ($q, $method) => $q->where('method', $method))
            ->when($request->query('from'), fn ($q, $from) => $q->whereDate('paid_at', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->whereDate('paid_at', '<=', $to))
            ->latest('paid_at')->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Payment::class);

        $invoices = Invoice::with('tenant:id,full_name')
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->orderBy('due_date')
            ->limit(200)
            ->get();

        return view('payments.create', [
            'invoices' => $invoices,
            'selectedInvoice' => $request->query('invoice_id'),
        ]);
    }

    public function store(Request $request, PaymentService $payments)
    {
        $this->authorize('create', Payment::class);

        $data = $request->validate([
            'invoice_id' => ['required', 'integer', Rule::exists('invoices', 'id')->where('organization_id', $request->user()->activeMembership()->organization_id)],
            'amount' => ['required', 'string', 'max:20'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'reference' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $invoice = Invoice::findOrFail($data['invoice_id']);

        $payment = $payments->record($invoice, [
            'amount' => Money::parse($data['amount']),
            'method' => $data['method'],
            'paid_at' => $data['paid_at'],
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], $request->user());

        return redirect()->route('payments.show', $payment)->with('status', 'Pembayaran dicatat.');
    }

    public function show(Payment $payment)
    {
        $this->authorize('view', $payment);

        $payment->load(['invoice.tenant', 'invoice.property', 'tenant', 'recordedBy:id,name']);

        return view('payments.show', compact('payment'));
    }

    public function reverse(Request $request, Payment $payment, PaymentService $payments)
    {
        $this->authorize('reverse', $payment);

        $data = $request->validate(['reason' => ['required', 'string', 'max:200']]);

        $payments->reverse($payment, $data['reason'], $request->user());

        return back()->with('status', 'Pembayaran dibatalkan dan saldo invoice diperbarui.');
    }

    public function receipt(Payment $payment)
    {
        $this->authorize('view', $payment);

        $payment->load(['invoice.tenant', 'invoice.property', 'invoice.organization', 'tenant']);

        return Pdf::loadView('pdf.receipt', compact('payment'))
            ->setPaper('a5')
            ->download('kuitansi-'.$payment->id.'.pdf');
    }
}
