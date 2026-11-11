<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use App\Models\Organization;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateInvoices extends Command
{
    protected $signature = 'nonikos:generate-invoices {--month= : Bulan (YYYY-MM), default bulan depan} {--organization= : ID organisasi tertentu}';

    protected $description = 'Membuat invoice untuk semua kontrak aktif pada periode terpilih (idempoten).';

    public function handle(BillingService $billing): int
    {
        $month = $this->option('month')
            ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth()
            : now()->addMonthNoOverflow()->startOfMonth();

        $organizationIds = $this->option('organization')
            ? [(int) $this->option('organization')]
            : Organization::where('status', 'active')->pluck('id')->all();

        $totals = ['created' => 0, 'skipped' => 0];

        foreach ($organizationIds as $id) {
            $result = TenantContext::run($id, fn () => $billing->runForMonth($id, $month->copy()));
            $totals['created'] += $result['created'];
            $totals['skipped'] += $result['skipped'];
            $this->line(sprintf('Organisasi #%d: %d dibuat, %d dilewati', $id, $result['created'], $result['skipped']));
        }

        $this->info(sprintf('Selesai. Total: %d dibuat, %d dilewati untuk periode %s.', $totals['created'], $totals['skipped'], $month->format('F Y')));

        return self::SUCCESS;
    }
}