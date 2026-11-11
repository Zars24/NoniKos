<?php

namespace Tests\Feature;

use App\Services\BillingService;
use App\Services\ContractService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_search_does_not_expose_other_tenants(): void
    {
        $org = $this->makeOrganization();

        TenantContext::run($org['organization']->id, function () use ($org) {
            $agreement = app(ContractService::class)->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->startOfMonth()->toDateString(),
                'duration_months' => 3,
            ]);

            app(BillingService::class)->generateForPeriod($agreement, today()->startOfMonth());

            \App\Models\Tenant::create([
                'organization_id' => $org['organization']->id,
                'code' => 'TNT-SECRET',
                'full_name' => 'Penyewa Rahasia',
                'status' => 'active',
            ]);
        });

        $this->actingAs($org['tenantUser'])
            ->get(route('search', ['q' => 'Rahasia']))
            ->assertOk()
            ->assertDontSee('Penyewa Rahasia');

        $this->actingAs($org['owner'])
            ->get(route('search', ['q' => 'Rahasia']))
            ->assertOk()
            ->assertSee('Penyewa Rahasia');
    }
}