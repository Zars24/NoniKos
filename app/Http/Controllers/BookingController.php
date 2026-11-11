<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\ActivityLogger;
use App\Services\ContractService;
use App\Support\CodeGenerator;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Booking::class);

        $bookings = Booking::query()
            ->with(['tenant:id,full_name', 'room:id,number', 'property:id,name'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest('start_date')
            ->paginate(20)
            ->withQueryString();

        return view('bookings.index', compact('bookings'));
    }

    public function create()
    {
        $this->authorize('create', Booking::class);

        return view('bookings.create', [
            'booking' => new Booking(['start_date' => today(), 'duration_months' => 12, 'status' => 'pending']),
            'rooms' => Room::with('property:id,name')->where('is_active', true)->orderBy('number')->get(),
            'tenants' => Tenant::where('status', 'active')->orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Booking::class);

        $data = $request->validate([
            'room_id' => ['required', 'integer', Rule::exists('rooms', 'id')->where('organization_id', $request->user()->activeMembership()->organization_id)],
            'tenant_id' => ['required', 'integer', Rule::exists('tenants', 'id')->where('organization_id', $request->user()->activeMembership()->organization_id)],
            'start_date' => ['required', 'date'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:60'],
            'monthly_price' => ['required', 'string', 'max:20'],
            'deposit_amount' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $room = Room::findOrFail($data['room_id']);

        $booking = Booking::create([
            'organization_id' => $room->organization_id,
            'property_id' => $room->property_id,
            'room_id' => $room->id,
            'tenant_id' => $data['tenant_id'],
            'code' => CodeGenerator::next(Booking::class, 'BKG'),
            'start_date' => $data['start_date'],
            'duration_months' => $data['duration_months'],
            'monthly_price' => Money::parse($data['monthly_price']),
            'deposit_amount' => Money::parse($data['deposit_amount'] ?? 0),
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);

        app(ActivityLogger::class)->log('booking.created', $booking, 'Booking '.$booking->code.' dibuat');

        return redirect()->route('bookings.index')->with('status', 'Booking dibuat.');
    }

    public function edit(Booking $booking)
    {
        $this->authorize('update', $booking);

        return view('bookings.edit', [
            'booking' => $booking,
            'rooms' => Room::with('property:id,name')->orderBy('number')->get(),
            'tenants' => Tenant::orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }

    public function update(Request $request, Booking $booking)
    {
        $this->authorize('update', $booking);

        abort_if(in_array($booking->status, ['converted', 'cancelled'], true), 422, 'Booking yang sudah dikonversi/dibatalkan tidak dapat diubah.');

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'duration_months' => ['required', 'integer', 'min:1', 'max:60'],
            'monthly_price' => ['required', 'string', 'max:20'],
            'deposit_amount' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking->update([
            'start_date' => $data['start_date'],
            'duration_months' => $data['duration_months'],
            'monthly_price' => Money::parse($data['monthly_price']),
            'deposit_amount' => Money::parse($data['deposit_amount'] ?? 0),
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect()->route('bookings.index')->with('status', 'Booking diperbarui.');
    }

    public function destroy(Booking $booking)
    {
        $this->authorize('delete', $booking);

        $booking->delete();

        return redirect()->route('bookings.index')->with('status', 'Booking dihapus.');
    }

    public function confirm(Booking $booking)
    {
        $this->authorize('update', $booking);

        abort_unless($booking->status === 'pending', 422, 'Hanya booking menunggu yang dapat dikonfirmasi.');

        $booking->update(['status' => 'confirmed']);

        return back()->with('status', 'Booking dikonfirmasi.');
    }

    public function cancel(Booking $booking)
    {
        $this->authorize('update', $booking);

        abort_if(in_array($booking->status, ['converted'], true), 422, 'Booking yang sudah dikonversi tidak dapat dibatalkan.');

        $booking->update(['status' => 'cancelled']);

        return back()->with('status', 'Booking dibatalkan.');
    }

    public function convert(Booking $booking, ContractService $contracts)
    {
        $this->authorize('update', $booking);

        $agreement = $contracts->convertBooking($booking);

        return redirect()->route('agreements.show', $agreement)->with('status', 'Booking dikonversi menjadi kontrak '.$agreement->code.'.');
    }
}
