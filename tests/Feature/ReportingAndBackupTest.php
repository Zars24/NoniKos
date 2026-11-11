<?php

namespace Tests\Feature;

use App\Models\BackupRecord;
use App\Models\Expense;
use App\Services\BackupService;
use App\Services\BillingService;
use App\Services\ContractService;
use App\Services\PaymentService;
use App\Services\ReportService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportingAndBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_financial_summary_uses_documented_definitions(): void
    {
        $org = $this->makeOrganization();

        TenantContext::run($org['organization']->id, function () use ($org) {
            $agreement = app(ContractService::class)->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->startOfMonth()->toDateString(),
                'duration_months' => 3,
            ]);

            $billing = app(BillingService::class);
            $invoice = $billing->generateForPeriod($agreement, today()->startOfMonth());
            $invoice->forceFill(['due_date' => today()->addDays(7)])->save();
            $billing->issue($invoice);

            app(PaymentService::class)->record($invoice, [
                'amount' => 500_000,
                'method' => 'cash',
                'paid_at' => today(),
            ], $org['owner']);

            Expense::create([
                'organization_id' => $org['organization_id'] ?? $org['organization']->id,
                'property_id' => $org['property']->id,
                'expense_category_id' => null,
                'description' => 'Perbaikan lampu',
                'amount' => 150_000,
                'spent_on' => today(),
                'recorded_by' => $org['owner']->id,
            ]);

            $summary = app(ReportService::class)->summary(today()->startOfMonth(), today()->endOfMonth());

            $this->assertSame($invoice->total, $summary['billed']);
            $this->assertSame(500_000, $summary['received']);
            $this->assertSame($invoice->total - 500_000, $summary['outstanding']);
            $this->assertSame(150_000, $summary['expenses']);
            $this->assertSame(350_000, $summary['cash_flow']);
        });
    }

    public function test_report_excludes_draft_and_cancelled_invoices(): void
    {
        $org = $this->makeOrganization();

        TenantContext::run($org['organization']->id, function () use ($org) {
            $agreement = app(ContractService::class)->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->startOfMonth()->toDateString(),
                'duration_months' => 3,
            ]);

            app(BillingService::class)->generateForPeriod($agreement, today()->startOfMonth());

            $summary = app(ReportService::class)->summary(today()->startOfMonth(), today()->endOfMonth());

            $this->assertSame(0, $summary['billed']);
            $this->assertSame(0, $summary['outstanding']);
        });
    }

    public function test_backup_is_recorded_with_checksum_and_downloadable(): void
    {
        Storage::fake('backups');

        $org = $this->makeOrganization();
        $admin = \App\Models\User::factory()->create(['is_platform_admin' => true]);

        $record = app(BackupService::class)->create($org['organization']->id, $org['owner']->id, 'manual');

        $this->assertSame('completed', $record->status, (string) $record->error);
        Storage::disk('backups')->assertExists($record->filename);
        $this->assertSame(hash('sha256', Storage::disk('backups')->get($record->filename)), $record->checksum);

        $this->actingAs($admin)
            ->get(route('platform.backups.download', $record))
            ->assertOk();
    }

    public function test_csv_export_neutralises_formula_injection(): void
    {
        $org = $this->makeOrganization();

        TenantContext::run($org['organization']->id, function () use ($org) {
            $org['tenant']->forceFill(['full_name' => '=HYPERLINK("http://evil.test")'])->save();

            $agreement = app(ContractService::class)->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->startOfMonth()->toDateString(),
                'duration_months' => 3,
            ]);

            app(BillingService::class)->generateForPeriod($agreement, today()->startOfMonth());
        });

        $response = $this->actingAs($org['finance'])
            ->get(route('reports.finance.export', 'csv'));

        $response->assertOk();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('\'=HYPERLINK', $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);
    }

    public function test_organization_owner_cannot_reach_database_backups(): void    {
        Storage::fake('backups');

        $org = $this->makeOrganization();
        $record = app(BackupService::class)->create($org['organization']->id, $org['owner']->id, 'manual');

        // A dump holds every organization's rows, so organization staff must not see it.
        $this->actingAs($org['owner'])->get(route('platform.backups.index'))->assertForbidden();
        $this->actingAs($org['owner'])->get(route('platform.backups.download', $record))->assertForbidden();
        $this->actingAs($org['owner'])->post(route('platform.backups.store'))->assertForbidden();
    }

    public function test_restore_requires_confirmation_phrase(): void
    {
        Storage::fake('backups');

        $org = $this->makeOrganization();
        $admin = \App\Models\User::factory()->create(['is_platform_admin' => true]);
        $record = app(BackupService::class)->create($org['organization']->id, $org['owner']->id, 'manual');

        $this->actingAs($admin)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->post(route('platform.backups.restore', $record), ['confirm' => 'yes'])
            ->assertSessionHasErrors('confirm');
    }

    public function test_restore_rejects_tampered_backup(): void
    {
        Storage::fake('backups');

        $org = $this->makeOrganization();
        $record = app(BackupService::class)->create($org['organization']->id, $org['owner']->id, 'manual');

        Storage::disk('backups')->put($record->filename, 'tampered');

        $result = app(BackupService::class)->restore($record->fresh(), $org['owner']->id);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('Checksum', (string) $result['error']);
    }

    public function test_non_owner_cannot_create_or_restore_backups(): void
    {
        Storage::fake('backups');

        $org = $this->makeOrganization();
        $record = app(BackupService::class)->create($org['organization']->id, $org['owner']->id, 'manual');

        $this->actingAs($org['manager'])->post(route('platform.backups.store'))->assertForbidden();
        $this->actingAs($org['manager'])->post(route('platform.backups.restore', $record), ['confirm' => 'RESTORE'])->assertForbidden();
    }
}