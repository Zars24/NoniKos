<?php

namespace App\Support;

/**
 * Holds the organization the current request/job operates on.
 * Set only by trusted middleware or console code, never from request input.
 */
class TenantContext
{
    protected static ?int $organizationId = null;

    public static function id(): ?int
    {
        return self::$organizationId;
    }

    public static function set(?int $organizationId): void
    {
        self::$organizationId = $organizationId;
    }

    public static function has(): bool
    {
        return self::$organizationId !== null;
    }

    public static function run(int $organizationId, callable $callback): mixed
    {
        $previous = self::$organizationId;
        self::$organizationId = $organizationId;

        try {
            return $callback();
        } finally {
            self::$organizationId = $previous;
        }
    }

    public static function forget(): void
    {
        self::$organizationId = null;
    }
}
