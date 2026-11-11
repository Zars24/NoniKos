<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active organization from the authenticated user's active membership.
 * The organization id from the browser is only ever used to *select* among memberships
 * that already exist, never trusted on its own.
 */
class EnsureOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            abort(403, 'Akun tidak aktif.');
        }

        if ($user->isPlatformAdmin()) {
            TenantContext::forget();
            return $next($request);
        }

        $sessionId = $request->session()->get('organization_id');
        $membership = $sessionId ? $user->membershipFor((int) $sessionId) : null;

        if (! $membership) {
            $membership = $user->activeMembership();
        }

        if (! $membership) {
            abort(403, 'Anda tidak memiliki akses ke organisasi mana pun.');
        }

        if (! $membership->isActive()) {
            abort(403, 'Keanggotaan organisasi tidak aktif.');
        }

        $request->session()->put('organization_id', $membership->organization_id);
        TenantContext::set($membership->organization_id);

        $request->attributes->set('membership', $membership);
        $request->attributes->set('organization', $membership->organization);

        return $next($request);
    }
}
