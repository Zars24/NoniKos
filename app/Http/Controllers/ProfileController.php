<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'theme_preference' => ['nullable', Rule::in(['light', 'dark', 'system'])],
        ]);

        if ($data['email'] !== $user->email) {
            $user->forceFill(['email_verified_at' => null]);
        }

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'theme_preference' => $data['theme_preference'] ?? $user->preferredTheme(),
        ])->save();

        return redirect()->route('profile.edit')->with('status', 'Profil diperbarui.');
    }

    public function updateTheme(Request $request)
    {
        $data = $request->validate([
            'theme_preference' => ['required', Rule::in(['light', 'dark', 'system'])],
        ]);

        $request->user()->forceFill(['theme_preference' => $data['theme_preference']])->save();

        return response()->noContent();
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('status', 'Kata sandi diperbarui.');
    }

    /**
     * Self-service deletion is blocked when the user is tied to financial history or is the last owner.
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        $data = $request->validateWithBag('userDeletion', ['password' => ['required', 'current_password']]);

        $ownsOrganization = DB::table('organization_memberships')
            ->where('user_id', $user->id)
            ->where('role', 'owner')
            ->where('status', 'active')
            ->exists();

        if ($ownsOrganization) {
            return back()->with('error', 'Akun owner tidak dapat dihapus sendiri. Alihkan kepemilikan atau hubungi dukungan.');
        }

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Akun dihapus.');
    }
}