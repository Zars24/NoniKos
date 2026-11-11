<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Organization;
use App\Services\BillingService;
use App\Support\TenantContext;
use Illuminate\Console\Command;

class ApplyLateFees extends Command
{
    protected $signature = 'nonikos:apply-late-fees {--organization=}';

    protected $description = 'Menerapkan denda keterlambatan sekali pada invoice yang lewat jatuh tempo (idempoten).';

    public function handle(BillingService $billing): int
    {
        $organizationIds = $this->option('organization')
            ? [(int) $this->option('organization')]
            : Organization::where('status', 'active')->pluck('id')->all();

        $applied = 0;

        foreach ($organizationIds as $id) {
            TenantContext::run($id, function () use ($id, $billing, &$applied) {
                Invoice::query()
                    ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
                    ->where('due_date', '<', today())
                    ->whereNull('late_fee_applied_at')
                    ->chunkById(100, function ($invoices) use ($billing, &$applied) {
                        foreach ($invoices as $invoice) {
                            if ($billing->applyLateFee($invoice)) {
                                $applied++;
                            }
                        }
                    });
            });
        }

        $this->info(sprintf('Selesai. %d invoice dikenakan denda.', $applied));

        return self::SUCCESS;
    }
}