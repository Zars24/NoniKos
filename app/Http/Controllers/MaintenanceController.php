<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceComment;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', MaintenanceRequest::class);

        $requests = MaintenanceRequest::with(['property:id,name', 'room:id,number', 'assignee:id,name'])
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('priority'), fn ($q, $priority) => $q->where('priority', $priority))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('maintenance.index', compact('requests'));
    }

    public function create()
    {
        $this->authorize('create', MaintenanceRequest::class);

        return view('maintenance.create', [
            'requestModel' => new MaintenanceRequest(['priority' => 'normal', 'category' => 'other', 'status' => 'open']),
            'properties' => Property::orderBy('name')->get(['id', 'name']),
            'rooms' => Room::with('property:id,name')->orderBy('number')->get(['id', 'number', 'property_id']),
            'tenants' => Tenant::orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', MaintenanceRequest::class);

        $data = $this->validated($request);
        $data['organization_id'] = $request->user()->activeMembership()->organization_id;

        $maintenance = MaintenanceRequest::create($data);

        app(ActivityLogger::class)->log('maintenance.created', $maintenance, 'Permintaan pemeliharaan dibuat: '.$maintenance->title);

        return redirect()->route('maintenance.show', $maintenance)->with('status', 'Permintaan pemeliharaan dibuat.');
    }

    public function show(MaintenanceRequest $maintenance)
    {
        $this->authorize('view', $maintenance);

        $maintenance->load(['property:id,name', 'room:id,number', 'tenant:id,full_name', 'assignee:id,name', 'comments.user:id,name']);

        return view('maintenance.show', [
            'maintenance' => $maintenance,
            'staff' => User::whereHas('memberships', function ($q) use ($maintenance) {
                $q->where('organization_id', $maintenance->organization_id)
                    ->whereIn('role', ['owner', 'manager', 'operations']);
            })->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(MaintenanceRequest $maintenance)
    {
        $this->authorize('update', $maintenance);

        return view('maintenance.edit', [
            'requestModel' => $maintenance,
            'properties' => Property::orderBy('name')->get(['id', 'name']),
            'rooms' => Room::with('property:id,name')->orderBy('number')->get(['id', 'number', 'property_id']),
            'tenants' => Tenant::orderBy('full_name')->get(['id', 'full_name']),
        ]);
    }

    public function update(Request $request, MaintenanceRequest $maintenance)
    {
        $this->authorize('update', $maintenance);

        $maintenance->update($this->validated($request));

        app(ActivityLogger::class)->log('maintenance.updated', $maintenance, 'Permintaan pemeliharaan diperbarui');

        return redirect()->route('maintenance.show', $maintenance)->with('status', 'Permintaan diperbarui.');
    }

    public function destroy(MaintenanceRequest $maintenance)
    {
        $this->authorize('delete', $maintenance);

        abort(403, 'Permintaan pemeliharaan tidak dapat dihapus; gunakan pembatalan.');
    }

    public function changeStatus(Request $request, MaintenanceRequest $maintenance)
    {
        $this->authorize('update', $maintenance);

        $data = $request->validate([
            'status' => ['required', Rule::in(MaintenanceRequest::STATUSES)],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'actual_cost' => ['nullable', 'string', 'max:20'],
        ]);

        $maintenance->forceFill([
            'status' => $data['status'],
            'assigned_to' => $data['assigned_to'] ?? $maintenance->assigned_to,
            'actual_cost' => isset($data['actual_cost']) && $data['actual_cost'] !== '' ? Money::parse($data['actual_cost']) : $maintenance->actual_cost,
            'completed_at' => $data['status'] === 'completed' ? now() : null,
        ])->save();

        app(ActivityLogger::class)->log('maintenance.status_changed', $maintenance, 'Status pemeliharaan diubah menjadi '.$data['status']);

        return back()->with('status', 'Status pemeliharaan diperbarui.');
    }

    public function storeComment(Request $request, MaintenanceRequest $maintenance)
    {
        $this->authorize('view', $maintenance);

        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);

        MaintenanceComment::create([
            'maintenance_request_id' => $maintenance->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back()->with('status', 'Komentar ditambahkan.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'property_id' => ['required', 'integer', Rule::exists('properties', 'id')->where('organization_id', $request->user()->activeMembership()->organization_id)],
            'room_id' => ['nullable', 'integer', Rule::exists('rooms', 'id')->where('organization_id', $request->user()->activeMembership()->organization_id)],
            'tenant_id' => ['nullable', 'integer', Rule::exists('tenants', 'id')->where('organization_id', $request->user()->activeMembership()->organization_id)],
            'title' => ['required', 'string', 'max:140'],
            'category' => ['required', Rule::in(MaintenanceRequest::CATEGORIES)],
            'description' => ['required', 'string', 'max:2000'],
            'priority' => ['required', Rule::in(MaintenanceRequest::PRIORITIES)],
            'estimated_cost' => ['nullable', 'string', 'max:20'],
        ]);

        $data['estimated_cost'] = isset($data['estimated_cost']) && $data['estimated_cost'] !== '' ? Money::parse($data['estimated_cost']) : null;

        return $data;
    }
}