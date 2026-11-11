<?php

namespace App\Support;

class Permissions
{
    public const ROLES = ['owner', 'manager', 'finance', 'operations', 'tenant'];

    /** permission => roles that have it */
    public const MATRIX = [
        'dashboard.view' => ['owner', 'manager', 'finance', 'operations'],
        'properties.view' => ['owner', 'manager', 'finance', 'operations'],
        'properties.create' => ['owner'],
        'properties.update' => ['owner'],
        'properties.delete' => ['owner'],
        'rooms.view' => ['owner', 'manager', 'finance', 'operations'],
        'rooms.manage' => ['owner', 'manager', 'operations'],
        'tenants.view' => ['owner', 'manager', 'finance', 'operations'],
        'tenants.manage' => ['owner', 'manager', 'operations'],
        'tenants.import' => ['owner', 'manager'],
        'contracts.view' => ['owner', 'manager', 'finance', 'operations'],
        'contracts.manage' => ['owner', 'manager', 'operations'],
        'invoices.view' => ['owner', 'manager', 'finance', 'tenant'],
        'invoices.create' => ['owner', 'finance'],
        'invoices.adjust' => ['owner', 'finance'],
        'invoices.cancel' => ['owner', 'finance'],
        'payments.view' => ['owner', 'manager', 'finance', 'tenant'],
        'payments.record' => ['owner', 'finance'],
        'payments.refund' => ['owner', 'finance'],
        'finance.reports.view' => ['owner', 'manager', 'finance'],
        'finance.expenses.manage' => ['owner', 'finance'],
        'finance.exports.create' => ['owner', 'finance'],
        'maintenance.view' => ['owner', 'manager', 'operations', 'tenant'],
        'maintenance.manage' => ['owner', 'manager', 'operations'],
        'staff.view' => ['owner'],
        'staff.manage' => ['owner'],
        'activity_logs.view' => ['owner'],
        'settings.view' => ['owner', 'manager', 'finance', 'operations'],
        'settings.manage' => ['owner'],
        'portal.access' => ['tenant'],
        'platform.access' => [],
    ];

    public static function all(): array
    {
        return array_keys(self::MATRIX);
    }

    public static function forRole(?string $role): array
    {
        if ($role === null) {
            return [];
        }

        return array_keys(array_filter(self::MATRIX, fn (array $roles) => in_array($role, $roles, true)));
    }

    public static function roleHas(?string $role, string $permission): bool
    {
        return in_array($role, self::MATRIX[$permission] ?? [], true);
    }

    public static function label(string $permission): string
    {
        return ucwords(str_replace(['.', '_'], [' · ', ' '], $permission));
    }
}
