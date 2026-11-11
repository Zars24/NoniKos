<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class CreateBackup extends Command
{
    protected $signature = 'nonikos:backup {--organization=}';

    protected $description = 'Membuat backup database ke penyimpanan privat dengan metadata dan checksum.';

    public function handle(BackupService $backups): int
    {
        $record = $backups->create(
            $this->option('organization') ? (int) $this->option('organization') : null,
            null,
            'scheduled'
        );

        if ($record->status === 'completed') {
            $this->info('Backup selesai: '.$record->filename.' ('.number_format(($record->size ?? 0) / 1024, 1).' KB)');

            return self::SUCCESS;
        }

        $this->error('Backup gagal: '.$record->error);

        return self::FAILURE;
    }
}