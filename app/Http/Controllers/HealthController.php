<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Unauthenticated liveness/readiness probe for uptime monitors.
 * Returns only booleans — never counts, names, or errors that could leak data.
 */
class HealthController extends Controller
{
    public function __invoke()
    {
        $checks = [
            'database' => $this->databaseOk(),
            'storage' => $this->storageOk(),
        ];

        $healthy = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'time' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    protected function databaseOk(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    protected function storageOk(): bool
    {
        try {
            $disk = Storage::disk('private');
            $probe = '.health-'.now()->timestamp;

            $disk->put($probe, '1');
            $ok = $disk->exists($probe);
            $disk->delete($probe);

            return $ok;
        } catch (Throwable) {
            return false;
        }
    }
}
