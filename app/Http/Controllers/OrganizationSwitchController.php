<?php

namespace App\Http\Controllers;

use App\Models\OrganizationMembership;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OrganizationSwitchController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'organization_id' => ['required', 'integer'],
        ]);

        $membership = OrganizationMembership::where('user_id', Auth::id())
            ->where('organization_id', $data['organization_id'])
            ->where('status', 'active')
            ->first();

        if (! $membership) {
            throw ValidationException::withMessages(['organization_id' => 'Anda tidak memiliki akses ke organisasi tersebut.']);
        }

        $request->session()->put('organization_id', $membership->organization_id);
        TenantContext::set($membership->organization_id);

        return redirect()->route('dashboard')->with('status', 'Organisasi aktif diubah.');
    }
}
