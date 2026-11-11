<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\RentalAgreement;
use App\Models\Room;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractService
{
    public function __construct(protected ActivityLogger $activity)
    {
    }

    public function createAgreement(array $data): RentalAgreement
    {
        return DB::transaction(function () use ($data) {
            /** @var Room $room */
            $room = Room::whereKey($data['room_id'])->lockForUpdate()->firstOrFail();
            $start = Carbon::parse($data['start_date']);
            $months = max(1, (int) ($data['duration_months'] ?? 1));
            $end = $start->copy()->addMonthsNoOverflow($months)->subDay();

            $this->assertRoomFree($room, $start, $end);

            $agreement = RentalAgreement::create([
                'organization_id' => $room->organization_id,
                'property_id' => $room->property_id,
                'room_id' => $room->id,
                'tenant_id' => $data['tenant_id'],
                'booking_id' => $data['booking_id'] ?? null,
                'code' => $this->nextCode('AGR'),
                'start_date' => $start,
                'end_date' => $end,
                'monthly_price' => (int) ($data['monthly_price'] ?? $room->monthly_price),
                'deposit_amount' => (int) ($data['deposit_amount'] ?? $room->deposit_amount),
                'billing_cycle_months' => (int) ($data['billing_cycle_months'] ?? $room->billing_cycle_months ?? 1),
                'due_days' => (int) ($data['due_days'] ?? 7),
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncRoomStatus($room, 'occupied', 'Kontrak '.$agreement->code.' dibuat');

            $this->activity->log('agreement.created', $agreement, 'Kontrak '.$agreement->code.' dibuat untuk kamar '.$room->number, [
                'tenant_id' => $agreement->tenant_id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ]);

            return $agreement;
        });
    }

    public function convertBooking(Booking $booking): RentalAgreement
    {
        return DB::transaction(function () use ($booking) {
            $locked = Booking::withoutGlobalScopes()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'converted') {
                $existing = RentalAgreement::withoutGlobalScopes()->where('booking_id', $locked->id)->first();
                if ($existing) {
                    return $existing;
                }
            }

            if (! in_array($locked->status, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages(['booking' => 'Booking ini tidak dapat dikonversi.']);
            }

            $agreement = $this->createAgreement([
                'room_id' => $locked->room_id,
                'tenant_id' => $locked->tenant_id,
                'booking_id' => $locked->id,
                'start_date' => $locked->start_date->toDateString(),
                'duration_months' => $locked->duration_months,
                'monthly_price' => $locked->monthly_price,
                'deposit_amount' => $locked->deposit_amount,
            ]);

            $locked->forceFill(['status' => 'converted'])->save();

            $this->activity->log('booking.converted', $locked, 'Booking '.$locked->code.' dikonversi ke kontrak '.$agreement->code);

            return $agreement;
        });
    }

    public function renew(RentalAgreement $agreement, string $newEndDate, ?int $monthlyPrice = null): RentalAgreement
    {
        return DB::transaction(function () use ($agreement, $newEndDate, $monthlyPrice) {
            $locked = RentalAgreement::withoutGlobalScopes()->whereKey($agreement->id)->lockForUpdate()->firstOrFail();
            $end = Carbon::parse($newEndDate);

            if ($end->lte($locked->end_date)) {
                throw ValidationException::withMessages(['end_date' => 'Tanggal akhir baru harus setelah tanggal akhir saat ini.']);
            }

            $locked->forceFill([
                'end_date' => $end,
                'monthly_price' => $monthlyPrice ?? $locked->monthly_price,
                'status' => 'active',
            ])->save();

            $this->activity->log('agreement.renewed', $locked, 'Kontrak '.$locked->code.' diperpanjang sampai '.$end->toDateString());

            return $locked;
        });
    }

    public function terminate(RentalAgreement $agreement, string $reason): RentalAgreement
    {
        return DB::transaction(function () use ($agreement, $reason) {
            $locked = RentalAgreement::withoutGlobalScopes()->whereKey($agreement->id)->lockForUpdate()->firstOrFail();

            $locked->forceFill([
                'status' => 'terminated',
                'termination_reason' => $reason,
                'end_date' => $locked->end_date->isFuture() ? today() : $locked->end_date,
            ])->save();

            $room = Room::withoutGlobalScopes()->whereKey($locked->room_id)->lockForUpdate()->firstOrFail();
            if ($room->status === 'occupied' && ! $room->agreements()->where('status', 'active')->exists()) {
                $this->syncRoomStatus($room, 'available', 'Kontrak '.$locked->code.' dihentikan');
            }

            $this->activity->log('agreement.terminated', $locked, 'Kontrak '.$locked->code.' dihentikan', ['reason' => $reason]);

            return $locked;
        });
    }

    public function checkIn(RentalAgreement $agreement): RentalAgreement
    {
        $agreement->forceFill(['checked_in_at' => today()])->save();
        $this->activity->log('agreement.checked_in', $agreement, 'Check-in kontrak '.$agreement->code);

        return $agreement;
    }

    public function checkOut(RentalAgreement $agreement): RentalAgreement
    {
        return DB::transaction(function () use ($agreement) {
            $agreement->forceFill(['checked_out_at' => today()])->save();
            $this->terminate($agreement, 'Check-out');
            $this->activity->log('agreement.checked_out', $agreement, 'Check-out kontrak '.$agreement->code);

            return $agreement->fresh();
        });
    }

    public function transfer(RentalAgreement $agreement, int $newRoomId, ?string $reason = null): RentalAgreement
    {
        return DB::transaction(function () use ($agreement, $newRoomId, $reason) {
            $old = RentalAgreement::withoutGlobalScopes()->whereKey($agreement->id)->lockForUpdate()->firstOrFail();
            $newRoom = Room::whereKey($newRoomId)->lockForUpdate()->firstOrFail();

            if ($newRoom->organization_id !== $old->organization_id) {
                throw ValidationException::withMessages(['new_room_id' => 'Kamar tujuan tidak valid.']);
            }

            if ($newRoom->id === $old->room_id) {
                throw ValidationException::withMessages(['new_room_id' => 'Kamar tujuan harus berbeda.']);
            }

            $this->assertRoomFree($newRoom, today(), $old->end_date);

            $this->terminate($old, $reason ?: 'Pindah kamar');

            $new = $this->createAgreement([
                'room_id' => $newRoom->id,
                'tenant_id' => $old->tenant_id,
                'start_date' => today()->toDateString(),
                'duration_months' => max(1, (int) ceil(today()->diffInDays($old->end_date) / 30)),
                'monthly_price' => $newRoom->monthly_price,
                'deposit_amount' => $old->deposit_amount,
                'billing_cycle_months' => $old->billing_cycle_months,
                'due_days' => $old->due_days,
                'notes' => 'Pindah dari kamar '.$old->room?->number,
            ]);

            $this->activity->log('agreement.transferred', $new, 'Penyewa pindah ke kamar '.$newRoom->number, [
                'from_agreement' => $old->id,
            ]);

            return $new;
        });
    }

    /**
     * Reject overlapping active agreements on the same room.
     * ponytail: overlap window check only; add a DB exclusion constraint if MariaDB supports it later.
     */
    public function assertRoomFree(Room $room, Carbon $start, Carbon $end): void
    {
        $conflict = RentalAgreement::withoutGlobalScopes()
            ->where('room_id', $room->id)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                'room_id' => 'Kamar '.$room->number.' sudah memiliki kontrak aktif pada rentang tanggal tersebut.',
            ]);
        }
    }

    public function syncRoomStatus(Room $room, string $status, string $reason): void
    {
        if ($room->status === $status) {
            return;
        }

        $from = $room->status;
        $room->forceFill(['status' => $status])->save();

        \App\Models\RoomStatusHistory::create([
            'organization_id' => $room->organization_id,
            'room_id' => $room->id,
            'from_status' => $from,
            'to_status' => $status,
            'changed_by' => auth()->id(),
            'reason' => $reason,
        ]);
    }

    public function nextCode(string $prefix): string
    {
        return \App\Support\CodeGenerator::next(RentalAgreement::class, $prefix);
    }
}
