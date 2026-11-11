<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

class CodeGenerator
{
    /**
     * Generate PREFIX-YYYY-0001 style codes. Codes are unique across the whole
     * installation because they double as human-facing identifiers and the
     * database enforces a global unique index on them.
     */
    public static function next(string $modelClass, string $prefix): string
    {
        /** @var Model $model */
        $model = new $modelClass();
        $year = now()->format('Y');

        $query = fn () => $model->newQuery()->withoutGlobalScopes()
            ->where('code', 'like', "{$prefix}-{$year}-%");

        $count = (clone $query())->count() + 1;

        do {
            $code = sprintf('%s-%s-%04d', $prefix, $year, $count++);
        } while ((clone $query())->where('code', $code)->exists());

        return $code;
    }
}
