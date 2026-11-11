<?php

namespace Tests;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Property;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OrganizationProvisioner;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function tearDown(): void
    {
        TenantContext::forget();
        parent::tearDown();
    }

    /**
     * Organization with one user per role plus a property, a room and a tenant.
     *
     * @return array{organization:Organization,owner:User,manager:User,finance:User,operations:User,tenant:Tenant,tenantUser:User,property:Property,room:Room}
     */
    protected function makeOrganization(string $name = 'Kos Melati Jaya'): array
    {
        $suffix = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));

        $organization = app(OrganizationProvisioner::class)->provision($name, User::factory()->create([
            'name' => 'Owner Utama',
            'email' => "owner-{$suffix}@example.test",
        ]));

        TenantContext::set($organization->id);

        $owner = $organization->memberships()->where('role', 'owner')->first()->user;

        $make = function (string $role) use ($organization, $suffix) {
            $user = User::factory()->create(['email' => "{$role}-{$suffix}@example.test"]);
            OrganizationMembership::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role' => $role,
                'status' => 'active',
            ]);

            return $user;
        };

        $manager = $make('manager');
        $finance = $make('finance');
        $operations = $make('operations');

        $property = Property::create([
            'organization_id' => $organization->id,
            'name' => 'Kos '.$name,
            'code' => 'MLT-'.strtoupper($suffix),
            'city' => 'Bandung',
            'is_active' => true,
        ]);

        $room = Room::create([
            'organization_id' => $organization->id,
            'property_id' => $property->id,
            'number' => 'A1',
            'monthly_price' => 1_500_000,
            'deposit_amount' => 500_000,
            'billing_cycle_months' => 1,
            'status' => 'available',
            'is_active' => true,
        ]);

        $tenantUser = $make('tenant');
        $tenant = Tenant::create([
            'organization_id' => $organization->id,
            'user_id' => $tenantUser->id,
            'code' => 'TNT-'.strtoupper($suffix),
            'full_name' => 'Budi Santoso',
            'phone' => '081200000001',
            'status' => 'active',
        ]);

        return compact('organization', 'owner', 'manager', 'finance', 'operations', 'tenant', 'tenantUser', 'property', 'room');
    }
}