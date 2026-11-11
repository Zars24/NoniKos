<?php

namespace App\Console\Commands;

use App\Models\BackupRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneBackups extends Command
{
    protected $signature = 'nonikos:prune-backups {--days=30 : Simpan backup selama N hari}';

    protected $description = 'Menghapus berkas backup yang melewati kebijakan retensi.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);
        $removed = 0;

        BackupRecord::where('created_at', '<', $cutoff)->where('trigger', '!=', 'pre-restore')->chunkById(50, function ($records) use (&$removed) {
            foreach ($records as $record) {
                if (Storage::disk($record->disk)->exists($record->filename)) {
                    Storage::disk($record->disk)->delete($record->filename);
                }

                // Keep the metadata row for the audit trail but mark it pruned.
                $record->update(['status' => 'pruned']);
                $removed++;
            }
        });

        $this->info(sprintf('Selesai. %d berkas backup dipangkas (metadata dipertahankan).', $removed));

        return self::SUCCESS;
    }
}