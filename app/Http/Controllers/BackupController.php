<?php

namespace App\Http\Controllers;

use App\Models\BackupRecord;
use App\Services\ActivityLogger;
use App\Services\BackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Whole-database backups. Restricted to platform admins by route middleware:
 * a dump contains every organization's data, so it must never be handed to a
 * single organization's staff.
 */
class BackupController extends Controller
{
    public function __construct(protected BackupService $backups)
    {
    }

    public function index()
    {
        $records = BackupRecord::latest('id')->paginate(20);

        return view('backups.index', ['records' => $records]);
    }

    public function store(Request $request)
    {
        $record = $this->backups->create(null, $request->user()?->id, 'manual');

        $message = $record->status === 'completed'
            ? 'Backup selesai: '.$record->filename
            : 'Backup gagal: '.$record->error;

        return back()->with($record->status === 'completed' ? 'status' : 'error', $message);
    }

    public function download(BackupRecord $backup)
    {
        abort_unless(Storage::disk('backups')->exists($backup->filename), 404);

        app(ActivityLogger::class)->log('backup.downloaded', $backup, 'Backup diunduh: '.$backup->filename);

        return Storage::disk('backups')->download($backup->filename);
    }

    /**
     * Restore takes a fresh safety backup first, then replays the dump.
     * Route is protected by platform.admin + password confirmation.
     */
    public function restore(Request $request, BackupRecord $backup)
    {
        $request->validate([
            'confirm' => ['required', 'in:RESTORE'],
        ]);

        $result = $this->backups->restore($backup, $request->user()?->id);

        return back()->with(
            $result['ok'] ? 'status' : 'error',
            $result['ok']
                ? 'Restore selesai. Backup pengaman dibuat: '.($result['safety'] ?? '-')
                : 'Restore gagal: '.$result['error']
        );
    }
}
