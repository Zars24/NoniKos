<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomPhoto;
use App\Models\RoomStatusHistory;
use App\Models\RoomType;
use App\Services\ActivityLogger;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Room::class);

        $rooms = Room::query()
            ->with(['property:id,name', 'building:id,name'])
            ->when($request->query('q'), function ($q, $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('number', 'like', "%{$term}%")
                        ->orWhere('facilities', 'like', "%{$term}%");
                });
            })
            ->when($request->query('property_id'), fn ($q, $id) => $q->where('property_id', $id))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('property_id')->orderBy('number')
            ->paginate(20)
            ->withQueryString();

        $properties = Property::orderBy('name')->get(['id', 'name']);

        return view('rooms.index', compact('rooms', 'properties'));
    }

    public function create(Request $request)
    {
        $this->authorize('create', Room::class);

        $properties = Property::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('rooms.create', [
            'room' => new Room(['status' => 'available', 'billing_cycle_months' => 1]),
            'properties' => $properties,
            'buildings' => Building::orderBy('name')->get(['id', 'property_id', 'name']),
            'roomTypes' => RoomType::orderBy('name')->get(['id', 'property_id', 'name', 'monthly_price', 'deposit_amount']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Room::class);

        $data = $this->validated($request);
        $room = Room::create($data);

        RoomStatusHistory::create([
            'organization_id' => $room->organization_id,
            'room_id' => $room->id,
            'from_status' => null,
            'to_status' => $room->status,
            'changed_by' => auth()->id(),
            'reason' => 'Kamar dibuat',
        ]);

        app(ActivityLogger::class)->log('room.created', $room, 'Kamar '.$room->number.' dibuat');

        return redirect()->route('rooms.index')->with('status', 'Kamar berhasil dibuat.');
    }

    public function edit(Room $room)
    {
        $this->authorize('update', $room);

        return view('rooms.edit', [
            'room' => $room,
            'properties' => Property::orderBy('name')->get(['id', 'name']),
            'buildings' => Building::orderBy('name')->get(['id', 'property_id', 'name']),
            'roomTypes' => RoomType::orderBy('name')->get(['id', 'property_id', 'name', 'monthly_price', 'deposit_amount']),
            'histories' => $room->statusHistories()->with('changedBy:id,name')->limit(20)->get(),
            'photos' => $room->photos()->latest()->get(),
        ]);
    }

    public function update(Request $request, Room $room)
    {
        $this->authorize('update', $room);

        $data = $this->validated($request, $room);

        // Operational status is managed through the status endpoint (keeps history consistent).
        unset($data['status']);

        $room->update($data);

        app(ActivityLogger::class)->log('room.updated', $room, 'Kamar '.$room->number.' diperbarui');

        return redirect()->route('rooms.index')->with('status', 'Kamar diperbarui.');
    }

    public function destroy(Room $room)
    {
        $this->authorize('delete', $room);

        if ($room->agreements()->exists()) {
            $room->update(['is_active' => false, 'status' => 'unavailable']);

            return back()->with('status', 'Kamar memiliki riwayat kontrak sehingga dinonaktifkan, bukan dihapus.');
        }

        $room->delete();

        return redirect()->route('rooms.index')->with('status', 'Kamar dihapus.');
    }

    public function changeStatus(Request $request, Room $room, ContractService $contracts)
    {
        $this->authorize('update', $room);

        $data = $request->validate([
            'status' => ['required', Rule::in(Room::STATUSES)],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        if ($data['status'] === 'occupied' && ! $room->agreements()->where('status', 'active')->exists()) {
            return back()->with('error', 'Status terisi hanya dapat berasal dari kontrak aktif.');
        }

        if ($room->agreements()->where('status', 'active')->exists() && $data['status'] !== 'occupied') {
            return back()->with('error', 'Kamar masih memiliki kontrak aktif. Akhiri kontrak terlebih dahulu.');
        }

        $contracts->syncRoomStatus($room, $data['status'], $data['reason'] ?? 'Perubahan status manual');

        app(ActivityLogger::class)->log('room.status_changed', $room, 'Status kamar '.$room->number.' diubah menjadi '.$data['status'], [
            'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('status', 'Status kamar diperbarui.');
    }

    public function storePhoto(Request $request, Room $room)
    {
        $this->authorize('update', $room);

        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'caption' => ['nullable', 'string', 'max:120'],
        ]);

        $path = $request->file('photo')->store("rooms/{$room->id}", 'private');

        RoomPhoto::create([
            'room_id' => $room->id,
            'path' => $path,
            'caption' => $request->input('caption'),
        ]);

        if (! $room->photo_path) {
            $room->update(['photo_path' => $path]);
        }

        return back()->with('status', 'Foto kamar diunggah.');
    }

    public function destroyPhoto(Room $room, RoomPhoto $photo)
    {
        $this->authorize('update', $room);

        abort_unless($photo->room_id === $room->id, 404);

        Storage::disk('private')->delete($photo->path);
        $photo->delete();

        if ($room->photo_path === $photo->path) {
            $room->update(['photo_path' => $room->photos()->value('path')]);
        }

        return back()->with('status', 'Foto dihapus.');
    }

    public function photo(Room $room)
    {
        $this->authorize('view', $room);

        abort_unless($room->photo_path && Storage::disk('private')->exists($room->photo_path), 404);

        return Storage::disk('private')->response($room->photo_path);
    }

    protected function validated(Request $request, ?Room $room = null): array
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('properties', 'id')->where('organization_id', $request->user()->activeMembership()->organization_id)],
            'building_id' => ['nullable', 'integer', Rule::exists('buildings', 'id')->where('organization_id', $request->user()->activeMembership()->organization_id)],
            'room_type_id' => ['nullable', 'integer', Rule::exists('room_types', 'id')->where('organization_id', $request->user()->activeMembership()->organization_id)],
            'number' => ['required', 'string', 'max:20', Rule::unique('rooms')->where('property_id', $request->input('property_id'))->ignore($room?->id)],
            'floor' => ['nullable', 'integer', 'min:0', 'max:200'],
            'monthly_price' => ['required', 'string', 'max:20'],
            'deposit_amount' => ['nullable', 'string', 'max:20'],
            'billing_cycle_months' => ['required', 'integer', 'min:1', 'max:24'],
            'status' => ['nullable', Rule::in(Room::STATUSES)],
            'facilities' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['monthly_price'] = \App\Support\Money::parse($data['monthly_price']);
        $data['deposit_amount'] = \App\Support\Money::parse($data['deposit_amount'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['status'] = $data['status'] ?? 'available';

        return $data;
    }
}
