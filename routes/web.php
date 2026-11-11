<?php

use App\Http\Controllers\AgreementController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\OrganizationSwitchController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentLinkController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\Platform\PlatformOrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Portal\PortalController;
use App\Http\Controllers\TenantController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    $user = auth()->user();

    return redirect()->route(
        $user->isPlatformAdmin() ? 'platform.organizations' : ($user->isTenant() ? 'portal.dashboard' : 'dashboard')
    );
})->name('home');

// Public liveness probe for uptime monitoring; exposes booleans only.
Route::get('/health', HealthController::class)->middleware('throttle:60,1')->name('health');

// Public payment-link landing + provider webhook (no auth, verified separately).
Route::get('/pay/{token}', [PaymentLinkController::class, 'show'])->name('payment-links.show');
Route::post('/webhooks/payments/{provider}', [PaymentWebhookController::class, 'handle'])
    ->middleware('throttle:120,1')
    ->name('webhooks.payments');

Route::middleware(['auth', 'organization'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('can:dashboard.view')->name('dashboard');

    Route::get('/search', SearchController::class)->name('search');

    Route::post('/organization/switch', OrganizationSwitchController::class)->name('organization.switch');

    Route::middleware('can:properties.view')->group(function () {
        Route::resource('properties', PropertyController::class)->except('show');
        Route::post('properties/{property}/buildings', [PropertyController::class, 'storeBuilding'])->name('properties.buildings.store');
        Route::delete('properties/{property}/buildings/{building}', [PropertyController::class, 'destroyBuilding'])->name('properties.buildings.destroy');
    });

    Route::middleware('can:rooms.view')->group(function () {
        Route::resource('rooms', RoomController::class)->except('show');
        Route::post('rooms/{room}/status', [RoomController::class, 'changeStatus'])->name('rooms.status');
        Route::post('rooms/{room}/photos', [RoomController::class, 'storePhoto'])->name('rooms.photos.store');
        Route::delete('rooms/{room}/photos/{photo}', [RoomController::class, 'destroyPhoto'])->name('rooms.photos.destroy');
        Route::get('rooms/{room}/photo', [RoomController::class, 'photo'])->name('rooms.photo');
    });

    Route::middleware('can:tenants.view')->group(function () {
        Route::resource('tenants', TenantController::class);
        Route::post('tenants/import', [TenantController::class, 'import'])->middleware('can:tenants.import')->name('tenants.import');
        Route::get('tenants-import/template', [TenantController::class, 'template'])->middleware('can:tenants.import')->name('tenants.import.template');
        Route::post('tenants/{tenant}/documents', [TenantController::class, 'storeDocument'])->name('tenants.documents.store');
        Route::get('tenants/{tenant}/documents/{document}', [TenantController::class, 'downloadDocument'])->name('tenants.documents.download');
    });

    Route::middleware('can:contracts.view')->group(function () {
        Route::resource('bookings', BookingController::class)->except('show');
        Route::post('bookings/{booking}/confirm', [BookingController::class, 'confirm'])->name('bookings.confirm');
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('bookings/{booking}/convert', [BookingController::class, 'convert'])->name('bookings.convert');

        Route::resource('agreements', AgreementController::class)->except('show');
        Route::get('agreements/{agreement}', [AgreementController::class, 'show'])->name('agreements.show');
        Route::get('agreements/{agreement}/pdf', [AgreementController::class, 'pdf'])->name('agreements.pdf');
        Route::post('agreements/{agreement}/renew', [AgreementController::class, 'renew'])->name('agreements.renew');
        Route::post('agreements/{agreement}/terminate', [AgreementController::class, 'terminate'])->name('agreements.terminate');
        Route::post('agreements/{agreement}/check-in', [AgreementController::class, 'checkIn'])->name('agreements.check-in');
        Route::post('agreements/{agreement}/check-out', [AgreementController::class, 'checkOut'])->name('agreements.check-out');
        Route::post('agreements/{agreement}/transfer', [AgreementController::class, 'transfer'])->name('agreements.transfer');
        Route::post('agreements/{agreement}/invoice', [AgreementController::class, 'generateInvoice'])->name('agreements.invoice');
    });

    Route::middleware('can:invoices.view')->group(function () {
        Route::resource('invoices', InvoiceController::class)->except('destroy');
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::post('invoices/{invoice}/payment-link', [PaymentLinkController::class, 'create'])->name('invoices.payment-link');
        Route::post('invoices/{invoice}/send-reminder', [InvoiceController::class, 'sendReminder'])->name('invoices.reminder');
        Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->middleware('can:invoices.cancel')->name('invoices.cancel');
        Route::post('invoices/{invoice}/late-fee', [InvoiceController::class, 'applyLateFee'])->middleware('can:invoices.adjust')->name('invoices.late-fee');
        Route::post('invoices/{invoice}/adjustments', [InvoiceController::class, 'storeAdjustment'])->middleware('can:invoices.adjust')->name('invoices.adjustments.store');
    });

    Route::middleware('can:payments.view')->group(function () {
        Route::resource('payments', PaymentController::class)->only('index', 'create', 'store', 'show');
        Route::post('payments/{payment}/reverse', [PaymentController::class, 'reverse'])->middleware('can:payments.refund')->name('payments.reverse');
        Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    });

    Route::get('reports/finance', [ReportController::class, 'finance'])
        ->middleware('can:finance.reports.view')->name('reports.finance');
    Route::get('reports/finance/export/{format}', [ReportController::class, 'export'])
        ->middleware(['can:finance.reports.view', 'can:finance.exports.create'])->name('reports.finance.export');

    Route::post('expenses/categories', [ExpenseController::class, 'storeCategory'])
        ->middleware('can:finance.expenses.manage')->name('expenses.categories.store');
    Route::resource('expenses', ExpenseController::class)->except('show')
        ->middleware('can:finance.expenses.manage');

    Route::middleware('can:maintenance.view')->group(function () {
        Route::resource('maintenance', MaintenanceController::class)->except('show');
        Route::get('maintenance/{maintenance}', [MaintenanceController::class, 'show'])->name('maintenance.show');
        Route::post('maintenance/{maintenance}/status', [MaintenanceController::class, 'changeStatus'])->name('maintenance.status');
        Route::post('maintenance/{maintenance}/comments', [MaintenanceController::class, 'storeComment'])->name('maintenance.comments.store');
    });

    Route::middleware('can:staff.view')->group(function () {
        Route::resource('staff', StaffController::class)->except('show');
        Route::post('staff/{staff}/toggle', [StaffController::class, 'toggle'])->name('staff.toggle');
    });

    Route::get('settings', [SettingsController::class, 'edit'])->middleware('can:settings.view')->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->middleware('can:settings.manage')->name('settings.update');
    Route::put('settings/templates/{template}', [SettingsController::class, 'updateTemplate'])->middleware('can:settings.manage')->name('settings.templates.update');

    Route::get('activity', [ActivityLogController::class, 'index'])->middleware('can:activity_logs.view')->name('activity.index');

    // Tenant portal
    Route::middleware('tenant.portal')->prefix('portal')->name('portal.')->group(function () {
        Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
        Route::get('invoices', [PortalController::class, 'invoices'])->name('invoices');
        Route::get('invoices/{invoice}', [PortalController::class, 'invoice'])->name('invoices.show');
        Route::get('invoices/{invoice}/pdf', [PortalController::class, 'invoicePdf'])->name('invoices.pdf');
        Route::post('invoices/{invoice}/pay', [PortalController::class, 'pay'])->name('invoices.pay');
        Route::get('payments', [PortalController::class, 'payments'])->name('payments');
        Route::get('notifications', [PortalController::class, 'notifications'])->name('notifications');
        Route::get('maintenance', [PortalController::class, 'maintenance'])->name('maintenance');
        Route::post('maintenance', [PortalController::class, 'storeMaintenance'])->name('maintenance.store');
    });

    });

// Profile is a user-level concern: available to any authenticated user,
// including platform admins and users without an organization membership.
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Platform administration: separate axis from organization roles.
    Route::middleware('platform.admin')->prefix('platform')->name('platform.')->group(function () {
        Route::get('organizations', [PlatformOrganizationController::class, 'index'])->name('organizations');
        Route::patch('organizations/{organization}/status', [PlatformOrganizationController::class, 'updateStatus'])
            ->name('organizations.status');

        // Backups are whole-database dumps: they contain every organization's data,
        // so only a platform admin may list, download, or restore them.
        Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
        Route::get('backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
        Route::post('backups/{backup}/restore', [BackupController::class, 'restore'])
            ->middleware('password.confirm')->name('backups.restore');
    });
});

require __DIR__.'/auth.php';
