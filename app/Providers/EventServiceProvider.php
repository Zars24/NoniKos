<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Authentication audit trail. These rows carry a null organization_id
        // because authentication happens before the organization middleware runs.
        Event::listen(\Illuminate\Auth\Events\Login::class, function ($event) {
            $this->logAuth('auth.login', $event->user, 'Login berhasil');
        });

        Event::listen(\Illuminate\Auth\Events\Logout::class, function ($event) {
            $this->logAuth('auth.logout', $event->user, 'Logout');
        });

        Event::listen(\Illuminate\Auth\Events\Failed::class, function ($event) {
            $this->logAuth('auth.failed', $event->user, 'Percobaan login gagal', [
                'email' => $event->credentials['email'] ?? null,
            ]);
        });
    }

    protected function logAuth(string $action, ?\App\Models\User $user, string $description, array $meta = []): void
    {
        \App\Models\ActivityLog::create([
            'organization_id' => null,
            'user_id' => $user?->id,
            'action' => $action,
            'subject_type' => $user ? $user::class : null,
            'subject_id' => $user?->id,
            'description' => $description,
            'meta' => $meta ?: null,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
