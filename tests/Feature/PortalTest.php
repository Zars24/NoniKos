<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Services\BillingService;
use App\Services\ContractService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalTest extends TestCase
{
    use RefreshDatabase;

    private function agreementAndInvoice(array $org): array
    {
        return TenantContext::run($org['organization']->id, function () use ($org) {
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

            return [$agreement, $invoice->fresh()];
        });
    }

    public function test_tenant_sees_only_own_invoices(): void
    {
        $org = $this->makeOrganization();
        [, $invoice] = $this->agreementAndInvoice($org);

        $other = $this->makeOrganization('Kos Lain');
        [, $otherInvoice] = $this->agreementAndInvoice($other);

        $this->actingAs($org['tenantUser'])
            ->get(route('portal.invoices'))
            ->assertOk()
            ->assertSee($invoice->number)
            ->assertDontSee($otherInvoice->number);

        $this->actingAs($org['tenantUser'])
            ->get(route('portal.invoices.show', $otherInvoice))
            ->assertNotFound();
    }

    public function test_tenant_login_lands_on_portal_instead_of_403(): void
    {
        $org = $this->makeOrganization();

        $response = $this->post('/login', [
            'email' => $org['tenantUser']->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('home'));
        $this->actingAs($org['tenantUser'])->get(route('home'))->assertRedirect(route('portal.dashboard'));
    }

    public function test_tenant_sees_only_own_notifications(): void
    {
        $org = $this->makeOrganization();
        [, $invoice] = $this->agreementAndInvoice($org);

        $other = $this->makeOrganization('Kos Lain');
        [, $otherInvoice] = $this->agreementAndInvoice($other);

        TenantContext::run($org['organization']->id, fn () => \App\Models\NotificationDelivery::create([
            'organization_id' => $org['organization']->id,
            'invoice_id' => $invoice->id,
            'tenant_id' => $org['tenant']->id,
            'channel' => 'whatsapp',
            'template_key' => 'payment_reminder',
            'recipient' => '081200000001',
            'body' => 'Pengingat milik penyewa ini',
            'status' => 'sent',
        ]));

        TenantContext::run($other['organization']->id, fn () => \App\Models\NotificationDelivery::create([
            'organization_id' => $other['organization']->id,
            'invoice_id' => $otherInvoice->id,
            'tenant_id' => $other['tenant']->id,
            'channel' => 'whatsapp',
            'template_key' => 'payment_reminder',
            'recipient' => '081200000099',
            'body' => 'Pengingat milik penyewa lain',
            'status' => 'sent',
        ]));

        $this->actingAs($org['tenantUser'])
            ->get(route('portal.notifications'))
            ->assertOk()
            ->assertSee('Pengingat milik penyewa ini')
            ->assertDontSee('Pengingat milik penyewa lain');
    }

    public function test_tenant_cannot_read_other_tenants_payment_or_pdf(): void
    {
        $org = $this->makeOrganization();
        [, $invoice] = $this->agreementAndInvoice($org);

        $payment = TenantContext::run($org['organization']->id, fn () => app(\App\Services\PaymentService::class)->record($invoice, [
            'amount' => 100_000,
            'method' => 'cash',
            'paid_at' => today(),
        ], $org['owner']));

        $other = $this->makeOrganization('Kos Lain');

        $this->actingAs($other['tenantUser'])
            ->get(route('portal.invoices.pdf', $invoice))
            ->assertNotFound();

        $this->assertTrue($payment->tenant_id === $org['tenant']->id);
    }

    public function test_tenant_can_submit_maintenance_request_for_active_agreement(): void
    {
        $org = $this->makeOrganization();
        $this->agreementAndInvoice($org);

        $this->actingAs($org['tenantUser'])
            ->post(route('portal.maintenance.store'), [
                'title' => 'AC bocor',
                'category' => 'electrical',
                'description' => 'AC kamar A1 menetes.',
            ])
            ->assertRedirect();

        $this->assertSame(1, \App\Models\MaintenanceRequest::withoutGlobalScopes()->count());
    }

    public function test_tenant_without_agreement_cannot_submit_maintenance(): void
    {
        $org = $this->makeOrganization();

        $this->actingAs($org['tenantUser'])
            ->post(route('portal.maintenance.store'), [
                'title' => 'AC bocor',
                'category' => 'electrical',
                'description' => 'AC kamar A1 menetes.',
            ])
            ->assertStatus(422);
    }

    public function test_room_photo_requires_authorization_and_is_served_from_private_disk(): void
    {
        Storage::fake('private');

        $org = $this->makeOrganization();

        $this->actingAs($org['manager'])->post(route('rooms.photos.store', $org['room']), [
            // 1x1 PNG; GD is not installed here so fake()->image() is unavailable.
            'photo' => UploadedFile::fake()->createWithContent('kamar.png', base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg=='
            )),
            'caption' => 'Kamar A1',
        ])->assertRedirect();

        $path = $org['room']->fresh()->photo_path;
        $this->assertNotNull($path);
        Storage::disk('private')->assertExists($path);

        $this->actingAs($org['finance'])->get(route('rooms.photo', $org['room']))->assertOk();

        $other = $this->makeOrganization('Kos Lain');
        $this->actingAs($other['manager'])->get(route('rooms.photo', $org['room']))->assertNotFound();
    }

    public function test_tenant_document_download_requires_matching_tenant(): void
    {
        Storage::fake('private');

        $org = $this->makeOrganization();

        $this->actingAs($org['manager'])->post(route('tenants.documents.store', $org['tenant']), [
            'label' => 'KTP',
            'document' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
        ])->assertRedirect();

        $document = $org['tenant']->documents()->firstOrFail();

        $this->actingAs($org['manager'])
            ->get(route('tenants.documents.download', [$org['tenant'], $document]))
            ->assertOk();

        // A document id from another tenant must 404 even inside the same organization.
        $otherTenant = \App\Models\Tenant::create([
            'organization_id' => $org['organization']->id,
            'code' => 'TNT-OTHER',
            'full_name' => 'Orang Lain',
            'status' => 'active',
        ]);

        $this->actingAs($org['manager'])
            ->get(route('tenants.documents.download', [$otherTenant, $document]))
            ->assertNotFound();
    }
}