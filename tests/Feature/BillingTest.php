<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Room;
use App\Services\BillingService;
use App\Services\ContractService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_billing_is_idempotent_for_the_same_period(): void
    {
        $org = $this->makeOrganization();
        $agreement = TenantContext::run($org['organization']->id, fn () => app(ContractService::class)->createAgreement([
            'room_id' => $org['room']->id,
            'tenant_id' => $org['tenant']->id,
            'start_date' => today()->startOfMonth()->toDateString(),
            'duration_months' => 6,
        ]));

        $billing = app(BillingService::class);
        $period = today()->startOfMonth();

        $first = $billing->generateForPeriod($agreement, $period);
        $second = $billing->generateForPeriod($agreement, $period);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Invoice::withoutGlobalScopes()->where('rental_agreement_id', $agreement->id)->count());
    }

    public function test_invoice_number_is_unique_per_organization_and_month(): void
    {
        $org = $this->makeOrganization();

        [$a, $b] = TenantContext::run($org['organization']->id, function () use ($org) {
            $service = app(ContractService::class);
            $billing = app(BillingService::class);

            $room = Room::create([
                'organization_id' => $org['organization']->id,
                'property_id' => $org['property']->id,
                'number' => 'B2',
                'monthly_price' => 1_000_000,
                'billing_cycle_months' => 1,
                'status' => 'available',
                'is_active' => true,
            ]);

            $first = $service->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->startOfMonth()->toDateString(),
                'duration_months' => 3,
            ]);

            $second = $service->createAgreement([
                'room_id' => $room->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->startOfMonth()->toDateString(),
                'duration_months' => 3,
            ]);

            return [
                $billing->generateForPeriod($first, today()->startOfMonth()),
                $billing->generateForPeriod($second, today()->startOfMonth()),
            ];
        });

        $this->assertNotSame($a->number, $b->number);
        $this->assertMatchesRegularExpression('#^INV/[A-Z0-9]+/\d{6}/\d{4}$#', $a->number);
    }

    public function test_overlapping_active_agreement_on_same_room_is_rejected(): void
    {
        $org = $this->makeOrganization();

        TenantContext::run($org['organization']->id, function () use ($org) {
            app(ContractService::class)->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->toDateString(),
                'duration_months' => 6,
            ]);

            $this->expectException(ValidationException::class);

            app(ContractService::class)->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->addMonth()->toDateString(),
                'duration_months' => 6,
            ]);
        });
    }

    public function test_late_fee_applies_once_after_due_date(): void
    {
        $org = $this->makeOrganization();

        TenantContext::run($org['organization']->id, function () use ($org) {
            $agreement = app(ContractService::class)->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->startOfMonth()->toDateString(),
                'duration_months' => 3,
            ]);

            $billing = app(BillingService::class);
            $invoice = $billing->generateForPeriod($agreement, today()->subMonth()->startOfMonth());
            $invoice->forceFill(['due_date' => today()->subDay()])->save();
            $billing->issue($invoice);

            $fee = (int) round($invoice->fresh()->total * 5 / 100);
            $after = $billing->applyLateFee($invoice->fresh(), 5);

            $this->assertNotNull($after);
            $this->assertSame($fee, $after->late_fee_total);
            $this->assertNull($billing->applyLateFee($after, 5), 'Late fee must not apply twice.');
        });
    }

    public function test_invoice_status_recomputes_after_payment(): void
    {
        $org = $this->makeOrganization();

        $invoice = TenantContext::run($org['organization']->id, function () use ($org) {
            $agreement = app(ContractService::class)->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->startOfMonth()->toDateString(),
                'duration_months' => 3,
            ]);

            $billing = app(BillingService::class);
            $invoice = $billing->generateForPeriod($agreement, today()->startOfMonth());
            $invoice->forceFill(['due_date' => today()->addDays(7)])->save();
            $billing->issue($invoice);

            return $invoice->fresh();
        });

        $this->assertSame('issued', $invoice->status);

        TenantContext::run($org['organization']->id, function () use ($invoice, $org) {
            app(\App\Services\PaymentService::class)->record($invoice, [
                'amount' => 500_000,
                'method' => 'cash',
                'paid_at' => today(),
            ], $org['owner']);

            $partial = $invoice->fresh();
            $this->assertSame('partially_paid', $partial->status);

            app(\App\Services\PaymentService::class)->record($partial, [
                'amount' => $partial->balance(),
                'method' => 'bank_transfer',
                'paid_at' => today(),
            ], $org['owner']);

            $paid = $invoice->fresh();
            $this->assertSame('paid', $paid->status);
            $this->assertSame(0, $paid->balance());
        });
    }
}