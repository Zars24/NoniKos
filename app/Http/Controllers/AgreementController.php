<?php

namespace App\Http\Controllers;

use App\Models\RentalAgreement;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\BillingService;
use App\Services\ContractService;
use App\Support\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgreementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', RentalAgreement::class);

        $agreements = RentalAgreement::query()
            ->with(['tenant:id,full_name', 'room:id,number', 'property:id,name'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('q'), fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('code', 'like', "%{$term}%")
                    ->orWhereHas('tenant', fn ($t) => $t->where('full_name', 'like', "%{$term}%"));
            }))
            ->latest('start_date')
            ->paginate(20)
            ->withQueryString();

        return view('agreements.index', compact('agreements'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', RentalAgreement::class);

        return view('agreements.create', [
            'agreement' => new RentalAgreement([
                'start_date' => today(),
                'billing_cycle_months' => 1,
                'due_days' => 7,
            ]),
            'rooms' => Room::with('property:id,name')->where('is_active', true)->orderBy('number')->get(),
            'tenants' => Tenant::where('status', 'active')->orderBy('full_name')->get(['id', 'full_name']),
            'selectedTenant' => $request->query('tenant_id'),
        ]);
    }

    public function store(Request $request, ContractService $contracts)
    {
        $this->authorize('create', RentalAgreement::class);

        $data = $request->validate([
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')->where('organization_id', $request->user()->activeMembership()->organization_id)],
            'tenant_id' => ['required', 'integer', Rule::exists('tenants', 'id')->where('organization_id', $request->user()->activeMembership()->organization_id)],
            'start_date' => ['required', 'date'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:60'],
            'monthly_price' => ['required', 'string', 'max:20'],
            'deposit_amount' => ['nullable', 'string', 'max:20'],
            'billing_cycle_months' => ['required', 'integer', 'min:1', 'max:24'],
            'due_days' => ['required', 'integer', 'min:0', 'max:90'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $agreement = $contracts->createAgreement([
            'room_id' => $data['room_id'],
            'tenant_id' => $data['tenant_id'],
            'start_date' => $data['start_date'],
            'duration_months' => $data['duration_months'],
            'monthly_price' => Money::parse($data['monthly_price']),
            'deposit_amount' => Money::parse($data['deposit_amount'] ?? 0),
            'billing_cycle_months' => $data['billing_cycle_months'],
            'due_days' => $data['due_days'],
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('agreements.show', $agreement)->with('status', 'Kontrak '.$agreement->code.' dibuat.');
    }

    public function show(RentalAgreement $agreement)
    {
        $this->authorize('view', $agreement);

        $agreement->load(['tenant', 'room.property', 'invoices' => fn ($q) => $q->latest('period_start')]);

        return view('agreements.show', compact('agreement'));
    }

    public function edit(RentalAgreement $agreement)
    {
        $this->authorize('update', $agreement);

        return view('agreements.edit', [
            'agreement' => $agreement,
            'rooms' => Room::with('property:id,name')->orderBy('number')->get(),
            'tenants' => Tenant::orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }

    public function update(Request $request, RentalAgreement $agreement)
    {
        $this->authorize('update', $agreement);

        $data = $request->validate([
            'due_days' => ['required', 'integer', 'min:0', 'max:90'],
            'billing_cycle_months' => ['required', 'integer', 'min:1', 'max:24'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $agreement->update($data);

        return redirect()->route('agreements.show', $agreement)->with('status', 'Kontrak diperbarui.');
    }

    public function destroy(RentalAgreement $agreement)
    {
        $this->authorize('delete', $agreement);

        abort(403, 'Kontrak tidak dapat dihapus; gunakan penghentian kontrak.');
    }

    public function renew(Request $request, RentalAgreement $agreement, ContractService $contracts)
    {
        $this->authorize('manage', $agreement);

        $data = $request->validate([
            'end_date' => ['required', 'date', 'after:today'],
            'monthly_price' => ['nullable', 'string', 'max:20'],
        ]);

        $contracts->renew($agreement, $data['end_date'], isset($data['monthly_price']) ? Money::parse($data['monthly_price']) : null);

        return back()->with('status', 'Kontrak diperpanjang.');
    }

    public function terminate(Request $request, RentalAgreement $agreement, ContractService $contracts)
    {
        $this->authorize('manage', $agreement);

        $data = $request->validate(['reason' => ['required', 'string', 'max:200']]);

        $contracts->terminate($agreement, $data['reason']);

        return back()->with('status', 'Kontrak dihentikan.');
    }

    public function checkIn(RentalAgreement $agreement, ContractService $contracts)
    {
        $this->authorize('manage', $agreement);

        $contracts->checkIn($agreement);

        return back()->with('status', 'Check-in dicatat.');
    }

    public function checkOut(RentalAgreement $agreement, ContractService $contracts)
    {
        $this->authorize('manage', $agreement);

        $contracts->checkOut($agreement);

        return back()->with('status', 'Check-out dicatat dan kontrak dihentikan.');
    }

    public function transfer(Request $request, RentalAgreement $agreement, ContractService $contracts)
    {
        $this->authorize('manage', $agreement);

        $data = $request->validate([
            'new_room_id' => ['required', 'integer', Rule::exists('rooms', 'id')->where('organization_id', $agreement->organization_id)],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        $new = $contracts->transfer($agreement, $data['new_room_id'], $data['reason'] ?? null);

        return redirect()->route('agreements.show', $new)->with('status', 'Penyewa dipindahkan ke kamar baru.');
    }

    public function generateInvoice(RentalAgreement $agreement, BillingService $billing)
    {
        $this->authorize('manage', $agreement);

        $invoice = $billing->generateForPeriod($agreement, today()->startOfMonth());

        return redirect()->route('invoices.show', $invoice)->with('status', 'Invoice periode ini tersedia.');
    }

    public function pdf(RentalAgreement $agreement)
    {
        $this->authorize('view', $agreement);

        $agreement->load(['tenant', 'room.property', 'organization']);

        return Pdf::loadView('pdf.agreement', compact('agreement'))
            ->setPaper('a4')
            ->download('kontrak-'.$agreement->code.'.pdf');
    }
}
