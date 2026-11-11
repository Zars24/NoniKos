<?php

namespace App\Providers;

use App\Support\TenantContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \App\Services\Integrations\PaymentGateway::class,
            \App\Services\Integrations\SandboxPaymentGateway::class
        );

        $this->app->bind(
            \App\Services\Integrations\WhatsAppSender::class,
            \App\Services\Integrations\WhatsAppCloudSender::class
        );
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        View::composer('*', function ($view) {
            if (auth()->check()) {
                $view->with('currentOrganization', TenantContext::has()
                    ? \App\Models\Organization::find(TenantContext::id())
                    : null);
            }
        });
    }
}
