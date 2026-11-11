<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Platform-level administration. Read-mostly by design: it can suspend an
 * organization but cannot read tenant financial data. Subscription billing is
 * intentionally not implemented (see docs/INTEGRATIONS.md).
 */
class PlatformOrganizationController extends Controller
{
    public function index(Request $request)
    {
        $organizations = Organization::query()
            ->withCount(['memberships', 'properties'])
            ->when($request->query('q'), fn ($q, $term) => $q->where(function ($inner) use ($term) {
                $inner->where('name', 'like', "%{$term}%")->orWhere('slug', 'like', "%{$term}%");
            }))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('platform.organizations', compact('organizations'));
    }

    public function updateStatus(Request $request, Organization $organization)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])],
        ]);

        $organization->update(['status' => $data['status']]);

        app(ActivityLogger::class)->log(
            'platform.organization_status',
            $organization,
            'Status organisasi diubah menjadi '.$data['status'],
            ['organization' => $organization->slug]
        );

        return back()->with('status', 'Status organisasi diperbarui.');
    }
}