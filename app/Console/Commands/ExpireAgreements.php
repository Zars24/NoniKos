<?php

namespace App\Console\Commands;

use App\Models\RentalAgreement;
use App\Services\ContractService;
use App\Support\TenantContext;
use Illuminate\Console\Command;

class ExpireAgreements extends Command
{
    protected $signature = 'nonikos:expire-agreements {--organization=}';

    protected $description = 'Menandai kontrak yang melewati tanggal akhir sebagai kedaluwarsa dan membebaskan kamar.';

    public function handle(ContractService $contracts): int
    {
        $count = 0;

        RentalAgreement::withoutGlobalScopes()
            ->where('status', 'active')
            ->where('end_date', '<', today())
            ->with('room')
            ->chunkById(100, function ($agreements) use ($contracts, &$count) {
                foreach ($agreements as $agreement) {
                    TenantContext::run($agreement->organization_id, function () use ($agreement, $contracts, &$count) {
                        $agreement->forceFill(['status' => 'expired'])->save();

                        $room = $agreement->room;
                        if ($room && $room->status === 'occupied' && ! $room->agreements()->where('status', 'active')->exists()) {
                            $contracts->syncRoomStatus($room, 'available', 'Kontrak '.$agreement->code.' berakhir');
                        }

                        $count++;
                    });
                }
            });

        $this->info(sprintf('Selesai. %d kontrak ditandai kedaluwarsa.', $count));

        return self::SUCCESS;
    }
}