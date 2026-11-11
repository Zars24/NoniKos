<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Property;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Property::class);

        $properties = Property::query()
            ->withCount(['rooms', 'rooms as occupied_rooms_count' => fn ($q) => $q->where('status', 'occupied')])
            ->when($request->query('q'), function ($q, $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%")
                        ->orWhere('city', 'like', "%{$term}%");
                });
            })
            ->when($request->query('status') === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('properties.index', compact('properties'));
    }

    public function create()
    {
        $this->authorize('create', Property::class);

        return view('properties.create', ['property' => new Property()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Property::class);

        $data = $this->validated($request);
        $property = Property::create($data);

        app(ActivityLogger::class)->log('property.created', $property, 'Properti '.$property->name.' dibuat');

        return redirect()->route('properties.index')->with('status', 'Properti berhasil dibuat.');
    }

    public function edit(Property $property)
    {
        $this->authorize('update', $property);

        $buildings = $property->buildings()->orderBy('name')->get();

        return view('properties.edit', compact('property', 'buildings'));
    }

    public function update(Request $request, Property $property)
    {
        $this->authorize('update', $property);

        $property->update($this->validated($request, $property));

        app(ActivityLogger::class)->log('property.updated', $property, 'Properti '.$property->name.' diperbarui');

        return redirect()->route('properties.index')->with('status', 'Properti diperbarui.');
    }

    public function destroy(Property $property)
    {
        $this->authorize('delete', $property);

        if ($property->rooms()->exists() || $property->agreements()->exists()) {
            $property->update(['is_active' => false]);

            return back()->with('status', 'Properti memiliki riwayat kamar/kontrak sehingga dinonaktifkan, bukan dihapus.');
        }

        $property->delete();

        return redirect()->route('properties.index')->with('status', 'Properti dihapus.');
    }

    public function storeBuilding(Request $request, Property $property)
    {
        $this->authorize('update', $property);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('buildings')->where('property_id', $property->id)],
            'floors' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        Building::create([
            'organization_id' => $property->organization_id,
            'property_id' => $property->id,
            'name' => $data['name'],
            'floors' => $data['floors'] ?? 1,
        ]);

        return back()->with('status', 'Gedung ditambahkan.');
    }

    public function destroyBuilding(Property $property, Building $building)
    {
        $this->authorize('update', $property);

        abort_unless($building->property_id === $property->id, 404);

        $building->delete();

        return back()->with('status', 'Gedung dihapus.');
    }

    protected function validated(Request $request, ?Property $property = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:80'],
            'province' => ['nullable', 'string', 'max:80'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_due_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
