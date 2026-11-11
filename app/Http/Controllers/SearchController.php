<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        $user = $request->user();

        $tenants = $rooms = $invoices = collect();

        // Tenant-role users must not search across the organization's data.
        if ($term !== '' && $user->isTenant()) {
            $own = $user->tenant()->first();

            if ($own) {
                $invoices = Invoice::with('tenant')
                    ->where('tenant_id', $own->id)
                    ->where('number', 'like', "%{$term}%")
                    ->limit(10)->get();
            }
        } elseif ($term !== '') {
            $tenants = Tenant::where(function ($q) use ($term) {
                $q->where('full_name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%");
            })->limit(10)->get();

            $rooms = Room::with('property')->where('number', 'like', "%{$term}%")->limit(10)->get();

            $invoices = Invoice::with('tenant')->where('number', 'like', "%{$term}%")->limit(10)->get();
        }

        return view('search', compact('term', 'tenants', 'rooms', 'invoices'));
    }
}