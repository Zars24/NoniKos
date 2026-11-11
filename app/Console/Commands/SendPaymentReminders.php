<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\RentalAgreement;
use App\Services\NotificationService;
use App\Support\TenantContext;
use Illuminate\Console\Command;

class SendPaymentReminders extends Command
{
    protected $signature = 'nonikos:send-reminders {--organization=} {--days=3}';

    protected $description = 'Mengirim pengingat tagihan via queue WhatsApp (gagal kirim tercatat, tidak diklaim terkirim).';

    public function handle(NotificationService $notifications): int
    {
        $days = (int) $this->option('days');
        $organizationIds = $this->option('organization')
            ? [(int) $this->option('organization')]
            : Organization::where('status', 'active')->pluck('id')->all();

        $sent = 0;
        $failed = 0;

        foreach ($organizationIds as $id) {
            TenantContext::run($id, function () use ($notifications, $days, &$sent, &$failed) {
                Invoice::with('tenant', 'agreement.room', 'organization')
                    ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
                    ->whereBetween('due_date', [today()->toDateString(), today()->addDays($days)->toDateString()])
                    ->whereHas('tenant', fn ($q) => $q->whereNotNull('phone'))
                    ->chunkById(100, function ($invoices) use ($notifications, &$sent, &$failed) {
                        foreach ($invoices as $invoice) {
                            $delivery = $notifications->sendTemplate(
                                'payment_reminder',
                                $invoice->tenant->phone,
                                $notifications->invoiceVariables($invoice),
                                $invoice,
                                $invoice->tenant
                            );

                            $delivery->status === 'sent' ? $sent++ : $failed++;
                        }
                    });
            });
        }

        $this->info(sprintf('Selesai. Terkirim: %d, gagal/tertunda: %d.', $sent, $failed));

        return self::SUCCESS;
    }
}