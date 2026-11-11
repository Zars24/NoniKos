<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_list_organizations(): void
    {
        $this->makeOrganization();

        $admin = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)
            ->get(route('platform.organizations'))
            ->assertOk()
            ->assertSee('Kos Melati Jaya');
    }

    public function test_regular_owner_cannot_access_platform_area(): void
    {
        $org = $this->makeOrganization();

        $this->actingAs($org['owner'])->get(route('platform.organizations'))->assertForbidden();
    }

    public function test_platform_admin_can_suspend_an_organization(): void
    {
        $org = $this->makeOrganization();
        $admin = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)
            ->patch(route('platform.organizations.status', $org['organization']), ['status' => 'suspended'])
            ->assertRedirect();

        $this->assertSame('suspended', $org['organization']->fresh()->status);
    }

    public function test_platform_admin_is_redirected_home_to_platform_area(): void
    {
        $admin = User::factory()->create(['is_platform_admin' => true]);

        $this->actingAs($admin)->get('/')->assertRedirect(route('platform.organizations'));
    }
}