<?php

namespace App\Models;

use App\Support\Permissions;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_platform_admin',
        'status',
        'theme_preference',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_platform_admin' => 'boolean',
    ];

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function activeMemberships(): HasMany
    {
        return $this->memberships()->where('status', 'active');
    }

    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class);
    }

    public function isPlatformAdmin(): bool
    {
        return (bool) $this->is_platform_admin;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Active membership for the current tenant context (or first active one).
     */
    public function activeMembership(): ?OrganizationMembership
    {
        $query = $this->activeMemberships()->with('organization');

        if (TenantContext::has()) {
            $membership = (clone $query)->where('organization_id', TenantContext::id())->first();
            if ($membership) {
                return $membership;
            }
        }

        return $query->orderBy('id')->first();
    }

    public function membershipFor(?int $organizationId): ?OrganizationMembership
    {
        if ($organizationId === null) {
            return null;
        }

        return $this->activeMemberships()->where('organization_id', $organizationId)->first();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isPlatformAdmin()) {
            return str_starts_with($permission, 'platform.');
        }

        $membership = $this->activeMembership();

        if (! $membership) {
            return false;
        }

        return Permissions::roleHas($membership->role, $permission);
    }

    public function roleName(): ?string
    {
        return $this->activeMembership()?->role;
    }

    public function isTenant(): bool
    {
        return $this->roleName() === 'tenant';
    }

    public function canAccessProperty(int $propertyId): bool
    {
        $membership = $this->activeMembership();

        if (! $membership) {
            return false;
        }

        if (in_array($membership->role, ['owner', 'finance'], true)) {
            return true;
        }

        $assigned = $membership->properties()->pluck('properties.id');

        // No assignment means organization-wide access (owner-like default for managers).
        return $assigned->isEmpty() || $assigned->contains($propertyId);
    }

    public function preferredTheme(): string
    {
        return in_array($this->theme_preference, ['light', 'dark', 'system'], true)
            ? $this->theme_preference
            : 'system';
    }
}
