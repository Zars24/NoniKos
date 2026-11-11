<?php

namespace Tests\Feature;

use App\Models\BackupRecord;
use App\Models\Expense;
use App\Models\MaintenanceRequest;
use App\Services\BillingService;
use App\Services\ContractService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders every admin page once as the owner to catch Blade/runtime errors
 * that unit-level tests do not touch. Not a substitute for behavioural tests.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_render_all_admin_pages(): void
    {
        $org = $this->makeOrganization();

        [$agreement, $invoice, $payment] = TenantContext::run($org['organization']->id, function () use ($org) {
            $agreement = app(ContractService::class)->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->startOfMonth()->toDateString(),
                'duration_months' => 3,
            ]);

            $billing = app(BillingService::class);
            $invoice = $billing->generateForPeriod($agreement, today()->startOfMonth());
            $billing->issue($invoice);

            $payment = app(\App\Services\PaymentService::class)->record($invoice, [
                'amount' => 100_000,
                'method' => 'cash',
                'paid_at' => today(),
            ], $org['owner']);

            $expense = Expense::create([
                'organization_id' => $org['organization']->id,
                'property_id' => $org['property']->id,
                'expense_category_id' => null,
                'spent_on' => today(),
                'amount' => 50_000,
                'description' => 'Lampu baru',
                'recorded_by' => $org['owner']->id,
            ]);

            MaintenanceRequest::create([
                'organization_id' => $org['organization']->id,
                'property_id' => $org['property']->id,
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'title' => 'Kran bocor',
                'category' => 'plumbing',
                'description' => 'Kran kamar mandi menetes.',
                'priority' => 'normal',
                'status' => 'open',
            ]);

            return [$agreement, $invoice->fresh(), $payment];
        });

        $maintenance = MaintenanceRequest::withoutGlobalScopes()->firstOrFail();
        $expense = Expense::withoutGlobalScopes()->firstOrFail();
        $backup = BackupRecord::create([
            'organization_id' => $org['organization']->id,
            'filename' => 'demo.sql',
            'disk' => 'backups',
            'status' => 'completed',
            'trigger' => 'manual',
        ]);

        $urls = [
            route('dashboard'),
            route('properties.index'),
            route('properties.create'),
            route('properties.edit', $org['property']),
            route('rooms.index'),
            route('rooms.create'),
            route('rooms.edit', $org['room']),
            route('tenants.index'),
            route('tenants.create'),
            route('tenants.show', $org['tenant']),
            route('tenants.edit', $org['tenant']),
            route('bookings.index'),
            route('bookings.create'),
            route('agreements.index'),
            route('agreements.create'),
            route('agreements.show', $agreement),
            route('agreements.edit', $agreement),
            route('invoices.index'),
            route('invoices.create'),
            route('invoices.show', $invoice),
            route('payments.index'),
            route('payments.create'),
            route('payments.show', $payment),
            route('expenses.index'),
            route('expenses.create'),
            route('expenses.edit', $expense),
            route('maintenance.index'),
            route('maintenance.create'),
            route('maintenance.show', $maintenance),
            route('maintenance.edit', $maintenance),
            route('reports.finance'),
            route('staff.index'),
            route('staff.create'),
            route('settings.edit'),
            route('activity.index'),
            route('search', ['q' => 'Budi']),
            route('profile.edit'),
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($org['owner'])->get($url);

            $this->assertSame(
                200,
                $response->status(),
                "GET {$url} returned {$response->status()}: ".substr(strip_tags($response->getContent()), 0, 400)
            );
        }

        $this->assertTrue($backup->exists);
    }

    public function test_platform_admin_can_render_backup_page(): void
    {
        $this->makeOrganization();
        $admin = \App\Models\User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)->get(route('platform.backups.index'))->assertOk();
    }

    public function test_tenant_portal_pages_render(): void
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

            return $billing->issue($invoice);
        });

        foreach ([
            route('portal.dashboard'),
            route('portal.invoices'),
            route('portal.invoices.show', $invoice),
            route('portal.payments'),
            route('portal.maintenance'),
        ] as $url) {
            $response = $this->actingAs($org['tenantUser'])->get($url);

            $this->assertSame(
                200,
                $response->status(),
                "GET {$url} returned {$response->status()}: ".substr(strip_tags($response->getContent()), 0, 400)
            );
        }
    }
}