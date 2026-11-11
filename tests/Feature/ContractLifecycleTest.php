<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Services\ContractService;
use App\Support\CodeGenerator;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContractLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_agreement_marks_the_room_occupied_with_history(): void
    {
        $org = $this->makeOrganization();

        TenantContext::run($org['organization']->id, function () use ($org) {
            $agreement = app(ContractService::class)->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->toDateString(),
                'duration_months' => 12,
            ]);

            $this->assertSame('occupied', $org['room']->fresh()->status);
            $this->assertSame(1, $org['room']->statusHistories()->count());
            $this->assertSame(today()->addMonthsNoOverflow(12)->subDay()->toDateString(), $agreement->end_date->toDateString());
        });
    }

    public function test_termination_frees_the_room_and_keeps_the_contract(): void
    {
        $org = $this->makeOrganization();

        TenantContext::run($org['organization']->id, function () use ($org) {
            $service = app(ContractService::class);

            $agreement = $service->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->toDateString(),
                'duration_months' => 12,
            ]);

            $service->terminate($agreement, 'Penyewa pindah kota');

            $this->assertSame('terminated', $agreement->fresh()->status);
            $this->assertSame('available', $org['room']->fresh()->status);
            $this->assertSame(1, \App\Models\RentalAgreement::withoutGlobalScopes()->count(), 'Contract must not be deleted.');
        });
    }

    public function test_booking_conversion_is_idempotent_and_marks_booking_converted(): void
    {
        $org = $this->makeOrganization();

        TenantContext::run($org['organization']->id, function () use ($org) {
            $booking = Booking::create([
                'organization_id' => $org['organization']->id,
                'property_id' => $org['property']->id,
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'code' => CodeGenerator::next(Booking::class, 'BKG'),
                'start_date' => today()->toDateString(),
                'duration_months' => 6,
                'monthly_price' => 1_500_000,
                'deposit_amount' => 500_000,
                'status' => 'confirmed',
            ]);

            $service = app(ContractService::class);

            $first = $service->convertBooking($booking);
            $second = $service->convertBooking($booking->fresh());

            $this->assertSame($first->id, $second->id);
            $this->assertSame('converted', $booking->fresh()->status);
            $this->assertSame(1, \App\Models\RentalAgreement::withoutGlobalScopes()->count());
        });
    }

    public function test_room_transfer_moves_tenant_to_new_room(): void
    {
        $org = $this->makeOrganization();

        TenantContext::run($org['organization']->id, function () use ($org) {
            $service = app(ContractService::class);

            $agreement = $service->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->toDateString(),
                'duration_months' => 6,
            ]);

            $newRoom = Room::create([
                'organization_id' => $org['organization']->id,
                'property_id' => $org['property']->id,
                'number' => 'B9',
                'monthly_price' => 2_000_000,
                'billing_cycle_months' => 1,
                'status' => 'available',
                'is_active' => true,
            ]);

            $new = $service->transfer($agreement, $newRoom->id, 'Pindah kamar lebih besar');

            $this->assertSame($newRoom->id, $new->room_id);
            $this->assertSame('terminated', $agreement->fresh()->status);
            $this->assertSame('occupied', $newRoom->fresh()->status);
            $this->assertSame('available', $org['room']->fresh()->status);
        });
    }

    public function test_renewal_requires_a_later_end_date(): void
    {
        $org = $this->makeOrganization();

        TenantContext::run($org['organization']->id, function () use ($org) {
            $service = app(ContractService::class);

            $agreement = $service->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->toDateString(),
                'duration_months' => 3,
            ]);

            $this->expectException(ValidationException::class);

            $service->renew($agreement, $agreement->end_date->toDateString());
        });
    }

    public function test_converted_booking_cannot_be_deleted(): void
    {
        $org = $this->makeOrganization();

        TenantContext::run($org['organization']->id, function () use ($org) {
            $booking = Booking::create([
                'organization_id' => $org['organization']->id,
                'property_id' => $org['property']->id,
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'code' => CodeGenerator::next(Booking::class, 'BKG'),
                'start_date' => today()->toDateString(),
                'duration_months' => 6,
                'monthly_price' => 1_500_000,
                'deposit_amount' => 0,
                'status' => 'confirmed',
            ]);

            app(ContractService::class)->convertBooking($booking);

            $this->actingAs($org['owner'])
                ->delete(route('bookings.destroy', $booking))
                ->assertForbidden();

            $this->assertNotNull($booking->fresh());
        });
    }
}