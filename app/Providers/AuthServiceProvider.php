<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalAgreement;
use App\Models\Room;
use App\Models\Tenant;
use App\Policies\AgreementPolicy;
use App\Policies\BookingPolicy;
use App\Policies\ExpensePolicy;
use App\Policies\InvoicePolicy;
use App\Policies\MaintenancePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\RoomPolicy;
use App\Policies\TenantPolicy;
use App\Support\Permissions;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Registered explicitly: Laravel's convention would look for
     * MaintenanceRequestPolicy and RentalAgreementPolicy, which do not exist.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Property::class => PropertyPolicy::class,
        Room::class => RoomPolicy::class,
        Tenant::class => TenantPolicy::class,
        Booking::class => BookingPolicy::class,
        RentalAgreement::class => AgreementPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Payment::class => PaymentPolicy::class,
        MaintenanceRequest::class => MaintenancePolicy::class,
        Expense::class => ExpensePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Every catalogue permission becomes a gate ability, e.g. can:dashboard.view
        foreach (Permissions::all() as $permission) {
            Gate::define($permission, fn ($user) => $user->hasPermission($permission));
        }
    }
}