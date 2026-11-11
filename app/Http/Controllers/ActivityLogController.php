<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\OrganizationMembership;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $organizationId = TenantContext::id();

        // ActivityLog has no global scope (auth events carry a null organization),
        // so isolation is enforced here: own-organization rows plus the auth events
        // of this organization's own members.
        $memberIds = OrganizationMembership::where('organization_id', $organizationId)->pluck('user_id');

        $logs = ActivityLog::with('user:id,name')
            ->where(function ($q) use ($organizationId, $memberIds) {
                $q->where('organization_id', $organizationId)
                    ->orWhere(fn ($inner) => $inner->whereNull('organization_id')->whereIn('user_id', $memberIds));
            })
            ->when($request->query('action'), fn ($q, $action) => $q->where('action', 'like', "%{$action}%"))
            ->when($request->query('user_id'), fn ($q, $id) => $q->where('user_id', $id))
            ->when($request->query('from'), fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($request->query('to'), fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('activity.index', compact('logs'));
    }
}
