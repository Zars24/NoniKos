<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant-role users are confined to the portal; staff are redirected away from it.
 */
class EnsureTenantPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isTenant()) {
            abort(403, 'Portal penyewa hanya untuk akun penyewa.');
        }

        if (! $user->tenant()->exists()) {
            abort(403, 'Akun Anda belum terhubung ke data penyewa.');
        }

        return $next($request);
    }
}
