<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Organization;
use App\Support\TenantContext;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'nonikos:mark-overdue {--organization=}';

    protected $description = 'Memperbarui status invoice yang lewat jatuh tempo menjadi overdue.';

    public function handle(): int
    {
        $organizationIds = Organization::where('status', 'active')->pluck('id')->all();
        $updated = 0;

        foreach ($organizationIds as $id) {
            TenantContext::run($id, function () use (&$updated) {
                Invoice::query()
                    ->whereNotIn('status', ['draft', 'cancelled', 'paid', 'overdue'])
                    ->where('due_date', '<', today())
                    ->chunkById(100, function ($invoices) use (&$updated) {
                        foreach ($invoices as $invoice) {
                            $invoice->recalculateStatus();
                            $invoice->save();
                            $updated++;
                        }
                    });
            });
        }

        $this->info(sprintf('Selesai. %d invoice diperbarui.', $updated));

        return self::SUCCESS;
    }
}