<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Structured context for operational triage. Never logs request bodies
            // or credentials; the input list above is excluded from validation flashes.
            \Illuminate\Support\Facades\Log::error('app.exception', [
                'type' => $e::class,
                'message' => $e->getMessage(),
                'method' => request()->method(),
                'path' => request()->path(),
                'user_id' => auth()->id(),
                'organization_id' => \App\Support\TenantContext::id(),
                'ip' => request()->ip(),
            ]);
        });
    }
}
