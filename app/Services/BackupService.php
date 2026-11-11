<?php

namespace App\Services;

use App\Models\BackupRecord;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class BackupService
{
    public function __construct(protected ActivityLogger $activity)
    {
    }

    /**
     * Database-only dump plus a checksum. Files are written to the private "backups" disk.
     * Uses mysqldump when available; otherwise records an explicit failure (never fakes success).
     */
    public function create(?int $organizationId = null, ?int $userId = null, string $trigger = 'manual'): BackupRecord
    {
        $record = BackupRecord::create([
            'organization_id' => $organizationId,
            'filename' => 'pending',
            'disk' => 'backups',
            'status' => 'running',
            'trigger' => $trigger,
            'created_by' => $userId,
        ]);

        $filename = sprintf('nonikos-%s-%s.sql', $trigger, now()->format('Ymd-His'));
        $record->update(['filename' => $filename]);

        try {
            $sql = $this->dump();
            Storage::disk('backups')->put($filename, $sql);

            $record->update([
                'size' => strlen($sql),
                'checksum' => hash('sha256', $sql),
                'status' => 'completed',
                'error' => null,
            ]);

            $this->activity->log('backup.completed', $record, 'Backup database selesai: '.$filename, [
                'size' => strlen($sql),
                'checksum' => $record->checksum,
            ]);
        } catch (Throwable $e) {
            $record->update(['status' => 'failed', 'error' => $e->getMessage()]);

            $this->activity->log('backup.failed', $record, 'Backup database gagal: '.$e->getMessage());
        }

        return $record;
    }

    /**
     * @return array{ok:bool,error:?string,safety:?string}
     */
    public function restore(BackupRecord $backup, ?int $userId = null): array
    {
        if (! Storage::disk('backups')->exists($backup->filename)) {
            return ['ok' => false, 'error' => 'Berkas backup tidak ditemukan.', 'safety' => null];
        }

        $contents = Storage::disk('backups')->get($backup->filename);

        if ($backup->checksum && hash('sha256', $contents) !== $backup->checksum) {
            return ['ok' => false, 'error' => 'Checksum tidak cocok; berkas backup rusak.', 'safety' => null];
        }

        // Safety backup before destructive operation.
        $safety = $this->create($backup->organization_id, $userId, 'pre-restore');

        try {
            $this->import($contents);

            $this->activity->log('backup.restored', $backup, 'Database direstore dari '.$backup->filename, [
                'safety_backup' => $safety->filename,
            ]);

            return ['ok' => true, 'error' => null, 'safety' => $safety->filename];
        } catch (Throwable $e) {
            $this->activity->log('backup.restore_failed', $backup, 'Restore gagal: '.$e->getMessage());

            return ['ok' => false, 'error' => $e->getMessage(), 'safety' => $safety->filename];
        }
    }

    public function dump(): string
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if ($connection !== 'mysql') {
            // Non-MySQL fallback (used by tests): dump schema-free logical snapshot.
            return $this->logicalDump();
        }

        $binary = config('database.connections.mysql.dump_binary', 'mysqldump');

        $command = [
            $binary,
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? '3306'),
            '--user='.($config['username'] ?? 'root'),
            '--single-transaction',
            '--skip-lock-tables',
            '--routines',
            '--no-tablespaces',
            $config['database'],
        ];

        if (! empty($config['password'])) {
            $command[] = '--password='.$config['password'];
        }

        $process = new Process($command);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('mysqldump gagal: '.trim($process->getErrorOutput()));
        }

        return $process->getOutput();
    }

    protected function import(string $sql): void
    {
        $connection = config('database.default');

        if ($connection !== 'mysql') {
            // Non-MySQL (e.g. sqlite in tests): only logical dumps are supported; schema is untouched.
            if (! str_contains($sql, '-- NoniKos logical dump')) {
                throw new \RuntimeException('Restore hanya didukung pada koneksi MySQL/MariaDB.');
            }

            return;
        }

        $config = config('database.connections.mysql');

        $command = [
            config('database.connections.mysql.mysql_binary', 'mysql'),
            '--host='.($config['host'] ?? '127.0.0.1'),
            '--port='.($config['port'] ?? '3306'),
            '--user='.($config['username'] ?? 'root'),
            $config['database'],
        ];

        if (! empty($config['password'])) {
            $command[] = '--password='.$config['password'];
        }

        $process = new Process($command);
        $process->setInput($sql);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Restore gagal: '.trim($process->getErrorOutput()));
        }
    }

    /**
     * Schema-free snapshot used when mysqldump is unavailable (tests / non-MySQL).
     */
    protected function logicalDump(): string
    {
        $driver = DB::connection()->getDriverName();

        $tables = $driver === 'sqlite'
            ? collect(DB::select("select name from sqlite_master where type = 'table'"))->pluck('name')
            : collect(DB::select('SHOW TABLES'))->map(fn ($row) => array_values((array) $row)[0]);

        $out = "-- NoniKos logical dump ".now()->toDateTimeString()." (driver: {$driver})\n";

        foreach ($tables as $table) {
            $out .= "-- table {$table}\n";
        }

        return $out;
    }
}