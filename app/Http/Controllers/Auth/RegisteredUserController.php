<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\OrganizationProvisioner;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, OrganizationProvisioner $provisioner): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'organization_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = DB::transaction(function () use ($data, $provisioner, $request) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'status' => 'active',
            ]);

            $organization = $provisioner->provision($data['organization_name'], $user);

            $request->session()->put('organization_id', $organization->id);

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        app(ActivityLogger::class)->log('auth.registered', $user, 'Organisasi baru didaftarkan: '.$data['organization_name']);

        return redirect()->route('dashboard')->with('status', 'Selamat datang di NoniKos. Organisasi Anda siap digunakan.');
    }
}
