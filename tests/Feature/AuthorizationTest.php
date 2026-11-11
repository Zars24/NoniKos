<?php

namespace Tests\Feature;

use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Support\Permissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_matrix_matches_role_expectations(): void
    {
        $this->assertTrue(Permissions::roleHas('owner', 'staff.view'));
        $this->assertFalse(Permissions::roleHas('manager', 'staff.view'));
        $this->assertFalse(Permissions::roleHas('finance', 'settings.manage'));
        $this->assertTrue(Permissions::roleHas('finance', 'invoices.create'));
        $this->assertFalse(Permissions::roleHas('operations', 'invoices.create'));
        $this->assertFalse(Permissions::roleHas('tenant', 'dashboard.view'));
        $this->assertTrue(Permissions::roleHas('tenant', 'portal.access'));
    }

    public function test_owner_can_open_staff_page_manager_cannot(): void
    {
        $org = $this->makeOrganization();

        $this->actingAs($org['owner'])->get(route('staff.index'))->assertOk();
        $this->actingAs($org['manager'])->get(route('staff.index'))->assertForbidden();
    }

    public function test_only_owner_can_open_activity_log(): void
    {
        $org = $this->makeOrganization();

        $this->actingAs($org['owner'])->get(route('activity.index'))->assertOk();
        $this->actingAs($org['manager'])->get(route('activity.index'))->assertForbidden();
        $this->actingAs($org['finance'])->get(route('activity.index'))->assertForbidden();
    }

    public function test_finance_can_view_reports_operations_cannot(): void
    {
        $org = $this->makeOrganization();

        $this->actingAs($org['finance']->fresh())->get(route('reports.finance'))->assertOk();
        $this->actingAs($org['operations']->fresh())->get(route('reports.finance'))->assertForbidden();
    }

    public function test_export_requires_export_permission(): void
    {
        $org = $this->makeOrganization();

        $this->actingAs($org['finance'])->get(route('reports.finance.export', 'csv'))->assertOk();
        $this->actingAs($org['manager'])->get(route('reports.finance.export', 'csv'))->assertForbidden();
    }

    public function test_tenant_role_is_confined_to_portal(): void
    {
        $org = $this->makeOrganization();

        $this->actingAs($org['tenantUser'])->get(route('portal.dashboard'))->assertOk();
        $this->actingAs($org['tenantUser'])->get(route('dashboard'))->assertForbidden();
        $this->actingAs($org['tenantUser'])->get(route('tenants.index'))->assertForbidden();
    }

    public function test_staff_cannot_open_tenant_portal(): void
    {
        $org = $this->makeOrganization();

        $this->actingAs($org['manager'])->get(route('portal.dashboard'))->assertForbidden();
    }

    public function test_room_management_is_denied_to_finance(): void
    {
        $org = $this->makeOrganization();

        $this->actingAs($org['finance'])->get(route('rooms.edit', $org['room']))->assertForbidden();
        $this->actingAs($org['manager'])->get(route('rooms.edit', $org['room']))->assertOk();
    }

    public function test_inactive_user_is_blocked(): void
    {
        $org = $this->makeOrganization();

        $org['manager']->forceFill(['status' => 'suspended'])->save();

        $this->actingAs($org['manager']->fresh())->get(route('dashboard'))->assertForbidden();
    }
}