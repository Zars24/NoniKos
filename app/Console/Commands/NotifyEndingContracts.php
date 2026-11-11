<?php

namespace App\Console\Commands;

use App\Models\RentalAgreement;
use App\Models\Organization;
use App\Services\NotificationService;
use App\Support\TenantContext;
use Illuminate\Console\Command;

class NotifyEndingContracts extends Command
{
    protected $signature = 'nonikos:notify-ending-contracts {--organization=} {--days=30}';

    protected $description = 'Memberi tahu penyewa yang kontraknya akan berakhir dalam N hari.';

    public function handle(NotificationService $notifications): int
    {
        $days = (int) $this->option('days');
        $organizationIds = $this->option('organization')
            ? [(int) $this->option('organization')]
            : Organization::where('status', 'active')->pluck('id')->all();

        $count = 0;

        foreach ($organizationIds as $id) {
            TenantContext::run($id, function () use ($notifications, $days, &$count) {
                RentalAgreement::with('tenant', 'room')
                    ->where('status', 'active')
                    ->whereBetween('end_date', [today()->toDateString(), today()->addDays($days)->toDateString()])
                    ->whereHas('tenant', fn ($q) => $q->whereNotNull('phone'))
                    ->chunkById(100, function ($agreements) use ($notifications, &$count) {
                        foreach ($agreements as $agreement) {
                            $notifications->sendTemplate('contract_ending', $agreement->tenant->phone, [
                                'nama_penyewa' => $agreement->tenant->full_name,
                                'kamar' => $agreement->room?->number ?? '-',
                                'organisasi' => config('app.name'),
                            ], null, $agreement->tenant);

                            $count++;
                        }
                    });
            });
        }

        $this->info(sprintf('Selesai. %d pemberitahuan diproses.', $count));

        return self::SUCCESS;
    }
}