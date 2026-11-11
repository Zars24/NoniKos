<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\PaymentLink;
use App\Services\ActivityLogger;
use App\Services\Integrations\PaymentGateway;
use App\Services\PaymentService;
use App\Support\Money;
use Illuminate\Http\Request;

class PaymentLinkController extends Controller
{
    public function __construct(protected PaymentGateway $gateway)
    {
    }

    /**
     * Public landing page. It never marks an invoice paid; only a verified webhook does.
     */
    public function show(string $token)
    {
        $link = PaymentLink::withoutGlobalScopes()->where('token', $token)->firstOrFail();
        $invoice = Invoice::withoutGlobalScopes()->with(['items', 'tenant:id,full_name', 'organization'])->findOrFail($link->invoice_id);

        return view('payments.link', [
            'link' => $link,
            'invoice' => $invoice,
            'isSimulation' => $link->provider === 'sandbox',
        ]);
    }

    public function create(Request $request, Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        if ($invoice->balance() <= 0) {
            return back()->with('error', 'Invoice ini tidak memiliki sisa tagihan.');
        }

        if (in_array($invoice->status, ['draft', 'cancelled'], true)) {
            return back()->with('error', 'Invoice belum diterbitkan atau sudah dibatalkan.');
        }

        $link = $this->gateway->createLink($invoice);

        app(ActivityLogger::class)->log('payment_link.created', $invoice, 'Tautan pembayaran dibuat untuk '.$invoice->number, [
            'provider' => $link->provider,
            'amount' => $link->amount,
        ]);

        return back()->with('status', 'Tautan pembayaran dibuat: '.$link->url);
    }
}
