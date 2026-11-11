<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\NotificationDelivery;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\Integrations\PaymentGateway;
use App\Services\NotificationService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    /**
     * Resolve the authenticated user's tenant strictly from the session user, never from a URL id.
     */
    protected function tenant(): Tenant
    {
        $tenant = Tenant::where('user_id', auth()->id())->first();

        abort_unless($tenant, 403, 'Akun ini belum terhubung ke data penyewa.');

        return $tenant;
    }

    public function dashboard()
    {
        $tenant = $this->tenant();

        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->latest('period_start')->limit(5)->get();

        $balance = (int) Invoice::where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
            ->get()->sum(fn (Invoice $i) => $i->balance());

        return view('portal.dashboard', [
            'tenant' => $tenant,
            'agreement' => $tenant->activeAgreement(),
            'invoices' => $invoices,
            'balance' => $balance,
            'payments' => Payment::where('tenant_id', $tenant->id)->where('status', 'confirmed')->latest('paid_at')->limit(5)->get(),
        ]);
    }

    public function invoices()
    {
        $tenant = $this->tenant();

        $invoices = Invoice::where('tenant_id', $tenant->id)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->latest('period_start')->paginate(10);

        return view('portal.invoices', compact('tenant', 'invoices'));
    }

    public function invoice(Invoice $invoice)
    {
        $tenant = $this->tenant();

        abort_unless($invoice->tenant_id === $tenant->id, 403, 'Anda tidak berhak melihat invoice ini.');

        $invoice->load('items', 'payments');

        return view('portal.invoice', compact('tenant', 'invoice'));
    }

    public function invoicePdf(Invoice $invoice)
    {
        $tenant = $this->tenant();

        abort_unless($invoice->tenant_id === $tenant->id, 403);

        $invoice->load(['items', 'tenant', 'property', 'agreement.room', 'organization']);

        return Pdf::loadView('pdf.invoice', compact('invoice'))
            ->setPaper('a4')
            ->download('invoice-'.str_replace('/', '-', $invoice->number).'.pdf');
    }

    public function pay(Request $request, Invoice $invoice, PaymentGateway $gateway)
    {
        $tenant = $this->tenant();

        abort_unless($invoice->tenant_id === $tenant->id, 403);

        if ($invoice->balance() <= 0 || in_array($invoice->status, ['draft', 'cancelled'], true)) {
            return back()->with('error', 'Invoice ini tidak dapat dibayar.');
        }

        $link = $gateway->createLink($invoice);

        return redirect()->route('payment-links.show', $link->token);
    }

    public function payments()
    {
        $tenant = $this->tenant();

        return view('portal.payments', [
            'tenant' => $tenant,
            'payments' => Payment::where('tenant_id', $tenant->id)->latest('paid_at')->paginate(10),
        ]);
    }

    public function notifications()
    {
        $tenant = $this->tenant();

        return view('portal.notifications', [
            'tenant' => $tenant,
            'notifications' => NotificationDelivery::where('tenant_id', $tenant->id)
                ->latest('id')->paginate(20),
        ]);
    }

    public function maintenance()
    {
        $tenant = $this->tenant();

        return view('portal.maintenance', [
            'tenant' => $tenant,
            'requests' => MaintenanceRequest::where('tenant_id', $tenant->id)->latest()->get(),
        ]);
    }

    public function storeMaintenance(Request $request)
    {
        $tenant = $this->tenant();
        $agreement = $tenant->activeAgreement();

        abort_unless($agreement, 422, 'Anda belum memiliki kontrak aktif.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'category' => ['required', \Illuminate\Validation\Rule::in(MaintenanceRequest::CATEGORIES)],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        MaintenanceRequest::create([
            'organization_id' => $tenant->organization_id,
            'property_id' => $agreement->property_id,
            'room_id' => $agreement->room_id,
            'tenant_id' => $tenant->id,
            'title' => $data['title'],
            'category' => $data['category'],
            'description' => $data['description'],
            'priority' => 'normal',
            'status' => 'open',
        ]);

        return back()->with('status', 'Keluhan dikirim. Pengelola akan menindaklanjuti.');
    }
}