<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\Permissions;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationProvisioner
{
    /**
     * Create an organization with its owner membership and default settings.
     */
    public function provision(string $name, User $owner): Organization
    {
        return DB::transaction(function () use ($name, $owner) {
            $organization = Organization::create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'email' => $owner->email,
                'invoice_prefix' => 'INV',
                'due_days' => 7,
                'late_fee_percent' => 0,
                'timezone' => 'Asia/Jakarta',
                'plan' => 'standard',
                'status' => 'active',
            ]);

            OrganizationMembership::create([
                'organization_id' => $organization->id,
                'user_id' => $owner->id,
                'role' => 'owner',
                'status' => 'active',
            ]);

            TenantContext::run($organization->id, function () use ($organization) {
                $defaults = app(NotificationService::class);

                foreach (\App\Models\MessageTemplate::KEYS as $key) {
                    \App\Models\MessageTemplate::create([
                        'organization_id' => $organization->id,
                        'key' => $key,
                        'name' => Permissions::label($key),
                        'body' => $defaults->defaultBody($key),
                        'is_active' => true,
                    ]);
                }
            });

            return $organization;
        });
    }

    public function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organisasi';
        $slug = $base;
        $i = 2;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
