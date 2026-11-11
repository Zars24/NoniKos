<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Property;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ContractService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_are_scoped_to_the_active_organization(): void
    {
        $a = $this->makeOrganization();
        $b = $this->makeOrganization();

        // makeOrganization() runs twice; second call left context on org B.
        TenantContext::set($a['organization']->id);
        $this->assertSame(1, Property::count());
        $this->assertSame($a['property']->id, Property::first()->id);

        TenantContext::set($b['organization']->id);
        $this->assertSame(1, Property::count());
        $this->assertSame($b['property']->id, Property::first()->id);

        // Without context everything is visible (used only by trusted jobs).
        TenantContext::forget();
        $this->assertSame(2, Property::withoutGlobalScopes()->count());
    }

    public function test_user_cannot_read_another_organizations_invoice_through_http(): void
    {
        $a = $this->makeOrganization();
        $b = $this->makeOrganization();

        $invoiceB = TenantContext::run($b['organization']->id, function () use ($b) {
            return $this->issueInvoiceFor($b);
        });

        // Owner of A navigates to B's invoice id: authorization denies cross-organization access.
        $response = $this->actingAs($a['owner'])->get(route('invoices.show', $invoiceB));

        $response->assertForbidden();
    }

    public function test_switching_session_organization_is_validated_against_membership(): void
    {
        $a = $this->makeOrganization();
        $b = $this->makeOrganization();

        // Owner A tries to switch to an organization they do not belong to.
        $response = $this
            ->actingAs($a['owner'])
            ->withSession(['organization_id' => $a['organization']->id])
            ->post(route('organization.switch'), ['organization_id' => $b['organization']->id]);

        $response->assertSessionHas('organization_id', $a['organization']->id);
    }

    public function test_creating_a_model_without_organization_context_throws(): void
    {
        $this->makeOrganization();
        TenantContext::forget();

        $this->expectException(\RuntimeException::class);

        Room::create([
            'number' => 'X1',
            'monthly_price' => 100000,
            'billing_cycle_months' => 1,
            'status' => 'available',
            'is_active' => true,
        ]);
    }

    private function issueInvoiceFor(array $org): Invoice
    {
        $agreement = app(ContractService::class)->createAgreement([
            'room_id' => $org['room']->id,
            'tenant_id' => $org['tenant']->id,
            'start_date' => today()->toDateString(),
            'duration_months' => 6,
        ]);

        return app(\App\Services\BillingService::class)
            ->generateForPeriod($agreement, today()->startOfMonth());
    }
}