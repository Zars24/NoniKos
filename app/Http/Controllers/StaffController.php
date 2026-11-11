<?php

namespace App\Http\Controllers;

use App\Models\OrganizationMembership;
use App\Models\Property;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\Permissions;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class StaffController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->hasPermission('staff.view'), 403);

        $memberships = OrganizationMembership::with(['user', 'properties:id,name'])
            ->where('organization_id', TenantContext::id())
            ->orderBy('role')
            ->get();

        return view('staff.index', [
            'memberships' => $memberships,
            'roles' => array_diff(Permissions::ROLES, ['tenant']),
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()->hasPermission('staff.manage'), 403);

        return view('staff.create', [
            'roles' => array_diff(Permissions::ROLES, ['tenant']),
            'properties' => Property::orderBy('name')->get(['id', 'name']),
            'permissionMatrix' => Permissions::MATRIX,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('staff.manage'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')],
            'password' => ['required', Rules\Password::defaults()],
            'role' => ['required', Rule::in(array_diff(Permissions::ROLES, ['tenant']))],
            'property_ids' => ['nullable', 'array'],
            'property_ids.*' => ['integer', Rule::exists('properties', 'id')->where('organization_id', TenantContext::id())],
        ]);

        $membership = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'status' => 'active',
            ]);

            $membership = OrganizationMembership::create([
                'organization_id' => TenantContext::id(),
                'user_id' => $user->id,
                'role' => $data['role'],
                'status' => 'active',
            ]);

            if (! empty($data['property_ids'])) {
                $membership->properties()->sync($data['property_ids']);
            }

            return $membership;
        });

        app(ActivityLogger::class)->log('staff.created', $membership, 'Staf ditambahkan: '.$data['name'], [
            'role' => $data['role'],
            'properties' => $data['property_ids'] ?? [],
        ]);

        return redirect()->route('staff.index')->with('status', 'Staf ditambahkan.');
    }

    public function edit(OrganizationMembership $staff)
    {
        abort_unless(auth()->user()->hasPermission('staff.manage'), 403);
        abort_unless($staff->organization_id === TenantContext::id(), 404);

        return view('staff.edit', [
            'membership' => $staff->load(['user', 'properties:id']),
            'roles' => array_diff(Permissions::ROLES, ['tenant']),
            'properties' => Property::orderBy('name')->get(['id', 'name']),
            'permissionMatrix' => Permissions::MATRIX,
        ]);
    }

    public function update(Request $request, OrganizationMembership $staff)
    {
        abort_unless($request->user()->hasPermission('staff.manage'), 403);
        abort_unless($staff->organization_id === TenantContext::id(), 404);
        abort_if($staff->user_id === $request->user()->id && $request->input('role') !== 'owner', 422, 'Anda tidak dapat menurunkan role akun Anda sendiri.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['required', Rule::in(array_diff(Permissions::ROLES, ['tenant']))],
            'property_ids' => ['nullable', 'array'],
            'property_ids.*' => ['integer', Rule::exists('properties', 'id')->where('organization_id', TenantContext::id())],
            'password' => ['nullable', Rules\Password::defaults()],
        ]);

        DB::transaction(function () use ($staff, $data) {
            $staff->user->update(['name' => $data['name']]);
            if (! empty($data['password'])) {
                $staff->user->update(['password' => Hash::make($data['password'])]);
            }

            $staff->update(['role' => $data['role']]);
            $staff->properties()->sync($data['property_ids'] ?? []);
        });

        app(ActivityLogger::class)->log('staff.updated', $staff, 'Staf diperbarui: '.$data['name'], ['role' => $data['role']]);

        return redirect()->route('staff.index')->with('status', 'Staf diperbarui.');
    }

    public function toggle(OrganizationMembership $staff)
    {
        abort_unless(auth()->user()->hasPermission('staff.manage'), 403);
        abort_unless($staff->organization_id === TenantContext::id(), 404);
        abort_if($staff->user_id === auth()->id(), 422, 'Anda tidak dapat menonaktifkan akun Anda sendiri.');

        $newStatus = $staff->status === 'active' ? 'inactive' : 'active';

        $staff->update(['status' => $newStatus]);

        if ($newStatus === 'inactive') {
            // Revoke sessions so a deactivated staff member loses access immediately.
            DB::table('sessions')->where('user_id', $staff->user_id)->delete();
        }

        app(ActivityLogger::class)->log('staff.status_changed', $staff, 'Status keanggotaan staf diubah menjadi '.$newStatus);

        return back()->with('status', $newStatus === 'inactive' ? 'Staf dinonaktifkan dan sesi dicabut.' : 'Staf diaktifkan kembali.');
    }

    public function destroy(OrganizationMembership $staff)
    {
        abort_unless(auth()->user()->hasPermission('staff.manage'), 403);
        abort_unless($staff->organization_id === TenantContext::id(), 404);
        abort_if($staff->user_id === auth()->id(), 422, 'Anda tidak dapat menghapus keanggotaan Anda sendiri.');

        if ($staff->role === 'owner' && OrganizationMembership::where('organization_id', $staff->organization_id)->where('role', 'owner')->where('status', 'active')->count() <= 1) {
            return back()->with('error', 'Organisasi harus memiliki minimal satu owner aktif.');
        }

        app(ActivityLogger::class)->log('staff.removed', $staff, 'Keanggotaan staf dihapus');

        $staff->delete();

        return redirect()->route('staff.index')->with('status', 'Keanggotaan staf dihapus.');
    }
}