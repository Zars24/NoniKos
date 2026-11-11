<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BillingService;
use App\Services\ContractService;
use App\Services\OrganizationProvisioner;
use App\Services\PaymentService;
use App\Support\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Synthetic development data. Never run against production.
 * All names/emails/phones here are fictional.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Seeder dilewati di environment production.');

            return;
        }

        $owner = User::firstOrCreate(
            ['email' => 'owner@nonikos.test'],
            ['name' => 'Owner Demo', 'password' => Hash::make('password'), 'status' => 'active']
        );

        $organization = Organization::where('slug', 'kos-melati-demo')->first()
            ?? app(OrganizationProvisioner::class)->provision('Kos Melati Demo', $owner);

        TenantContext::run($organization->id, function () use ($organization, $owner) {
            $this->seedStaff($organization, $owner);

            $property = Property::firstOrCreate(
                ['organization_id' => $organization->id, 'code' => 'MLT'],
                ['name' => 'Kos Melati', 'city' => 'Bandung', 'address' => 'Jl. Melati No. 10', 'is_active' => true]
            );

            $rooms = collect(range(1, 8))->map(fn (int $i) => Room::firstOrCreate(
                ['organization_id' => $organization->id, 'property_id' => $property->id, 'number' => 'A'.$i],
                [
                    'monthly_price' => 1_500_000 + ($i * 50_000),
                    'deposit_amount' => 500_000,
                    'billing_cycle_months' => 1,
                    'status' => 'available',
                    'facilities' => 'AC, kasur, lemari, kamar mandi dalam',
                    'is_active' => true,
                ]
            ));

            $tenants = collect([
                ['Budi Santoso', 'budi@example.test', '081200000001'],
                ['Siti Aminah', 'siti@example.test', '081200000002'],
                ['Rizky Pratama', 'rizky@example.test', '081200000003'],
            ])->map(fn (array $t, int $i) => Tenant::firstOrCreate(
                ['organization_id' => $organization->id, 'email' => $t[1]],
                [
                    'code' => sprintf('TNT-%s-%04d', now()->format('Y'), $i + 1),
                    'full_name' => $t[0],
                    'phone' => $t[2],
                    'status' => 'active',
                ]
            ));

            // Give each demo tenant a portal login so the tenant-facing pages can be exercised.
            foreach ($tenants as $tenant) {
                $tenantUser = User::firstOrCreate(
                    ['email' => $tenant->email],
                    ['name' => $tenant->full_name, 'password' => Hash::make('password'), 'status' => 'active']
                );

                $tenant->forceFill(['user_id' => $tenantUser->id])->save();

                $organization->memberships()->firstOrCreate(
                    ['user_id' => $tenantUser->id],
                    ['role' => 'tenant', 'status' => 'active']
                );
            }

            $contracts = app(ContractService::class);
            $billing = app(BillingService::class);
            $payments = app(PaymentService::class);

            foreach ($tenants as $index => $tenant) {
                $room = $rooms[$index];

                if ($room->agreements()->where('status', 'active')->exists()) {
                    continue;
                }

                $agreement = $contracts->createAgreement([
                    'room_id' => $room->id,
                    'tenant_id' => $tenant->id,
                    'start_date' => today()->startOfMonth()->toDateString(),
                    'duration_months' => 6,
                    'due_days' => 7,
                ]);

                $invoice = $billing->generateForPeriod($agreement, today()->startOfMonth());
                $billing->issue($invoice);

                // First tenant pays in full, second partially, third not at all.
                $amount = match ($index) {
                    0 => $invoice->balance(),
                    1 => intdiv($invoice->balance(), 2),
                    default => 0,
                };

                if ($amount > 0) {
                    $payments->record($invoice, [
                        'amount' => $amount,
                        'method' => $index === 0 ? 'bank_transfer' : 'cash',
                        'paid_at' => today(),
                    ], $owner);
                }
            }

            $category = ExpenseCategory::firstOrCreate(
                ['organization_id' => $organization->id, 'name' => 'Pemeliharaan']
            );

            Expense::firstOrCreate(
                ['organization_id' => $organization->id, 'description' => 'Servis AC kamar A2'],
                [
                    'property_id' => $property->id,
                    'expense_category_id' => $category->id,
                    'amount' => 250_000,
                    'spent_on' => today(),
                    'recorded_by' => $owner->id,
                ]
            );
        });

        $this->command?->info('Data demo dibuat. Login: owner@nonikos.test / password');
    }

    private function seedStaff(Organization $organization, User $owner): void
    {
        $roles = [
            'manager' => 'Manajer Demo',
            'finance' => 'Finance Demo',
            'operations' => 'Operasional Demo',
        ];

        foreach ($roles as $role => $name) {
            $user = User::firstOrCreate(
                ['email' => "{$role}@nonikos.test"],
                ['name' => $name, 'password' => Hash::make('password'), 'status' => 'active']
            );

            $organization->memberships()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => $role, 'status' => 'active']
            );
        }
    }
}