<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentLink;
use App\Models\PaymentWebhookEvent;
use App\Services\BillingService;
use App\Services\ContractService;
use App\Services\PaymentService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function issuedInvoice(array $org): Invoice
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

            return $billing->issue($invoice);
        });
    }

    public function test_overpayment_is_rejected(): void
    {
        $org = $this->makeOrganization();
        $invoice = $this->issuedInvoice($org);

        TenantContext::run($org['organization']->id, function () use ($invoice, $org) {
            $this->expectException(ValidationException::class);

            app(PaymentService::class)->record($invoice, [
                'amount' => $invoice->total + 1,
                'method' => 'cash',
                'paid_at' => today(),
            ], $org['owner']);
        });
    }

    public function test_draft_invoice_cannot_be_paid(): void
    {
        $org = $this->makeOrganization();

        TenantContext::run($org['organization']->id, function () use ($org) {
            $agreement = app(ContractService::class)->createAgreement([
                'room_id' => $org['room']->id,
                'tenant_id' => $org['tenant']->id,
                'start_date' => today()->startOfMonth()->toDateString(),
                'duration_months' => 3,
            ]);

            $invoice = app(BillingService::class)->generateForPeriod($agreement, today()->startOfMonth());

            $this->expectException(ValidationException::class);

            app(PaymentService::class)->record($invoice, [
                'amount' => 100000,
                'method' => 'cash',
                'paid_at' => today(),
            ], $org['owner']);
        });
    }

    public function test_reversal_keeps_row_and_restores_balance(): void
    {
        $org = $this->makeOrganization();
        $invoice = $this->issuedInvoice($org);

        TenantContext::run($org['organization']->id, function () use ($invoice, $org) {
            $service = app(PaymentService::class);

            $payment = $service->record($invoice, [
                'amount' => 400_000,
                'method' => 'cash',
                'paid_at' => today(),
            ], $org['owner']);

            $this->assertSame('partially_paid', $invoice->fresh()->status);

            $service->reverse($payment, 'Salah input', $org['owner']);

            $reversed = Payment::withoutGlobalScopes()->find($payment->id);
            $this->assertNotNull($reversed, 'Reversal must keep the audit row.');
            $this->assertSame('reversed', $reversed->status);

            $this->assertSame(0, $invoice->fresh()->amount_paid);
            $this->assertSame('issued', $invoice->fresh()->status);
        });
    }

    public function test_reversal_of_already_reversed_payment_is_rejected(): void
    {
        $org = $this->makeOrganization();
        $invoice = $this->issuedInvoice($org);

        TenantContext::run($org['organization']->id, function () use ($invoice, $org) {
            $service = app(PaymentService::class);
            $payment = $service->record($invoice, [
                'amount' => 100_000,
                'method' => 'cash',
                'paid_at' => today(),
            ], $org['owner']);

            $service->reverse($payment, 'Batal', $org['owner']);

            $this->expectException(ValidationException::class);

            $service->reverse($payment->fresh(), 'Batal lagi', $org['owner']);
        });
    }

    public function test_webhook_without_configured_secret_is_rejected(): void
    {
        config()->set('services.payment_gateway.webhook_secret', '');

        $response = $this->postJson(route('webhooks.payments', 'gateway'), [
            'event_id' => 'evt_1',
            'reference' => 'tok_1',
            'amount' => 100000,
            'status' => 'paid',
            'currency' => 'IDR',
        ]);

        $response->assertStatus(400);
        $this->assertSame(0, PaymentWebhookEvent::count());
    }

    public function test_webhook_signature_verification_and_idempotency(): void
    {
        $org = $this->makeOrganization();
        $invoice = $this->issuedInvoice($org);

        config()->set('services.payment_gateway.webhook_secret', 'test-secret');

        $link = TenantContext::run($org['organization']->id, fn () => PaymentLink::create([
            'invoice_id' => $invoice->id,
            'provider' => 'gateway',
            'token' => 'tok_test_123',
            'amount' => $invoice->balance(),
            'status' => 'pending',
            'url' => 'https://example.test/pay',
        ]));

        $payload = json_encode([
            'event_id' => 'evt_abc',
            'event_type' => 'payment.paid',
            'reference' => $link->token,
            'amount' => $invoice->balance(),
            'currency' => 'IDR',
            'status' => 'paid',
        ]);

        $signature = hash_hmac('sha256', $payload, 'test-secret');

        $first = $this->call('POST', route('webhooks.payments', 'gateway'), [], [], [], [
            'HTTP_X-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $first->assertOk();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(1, Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->count());

        // Replaying the same event id must not create a second payment.
        $second = $this->call('POST', route('webhooks.payments', 'gateway'), [], [], [], [
            'HTTP_X-Signature' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $second->assertOk();
        $this->assertSame(1, Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->count());
    }

    public function test_webhook_amount_mismatch_is_rejected(): void
    {
        $org = $this->makeOrganization();
        $invoice = $this->issuedInvoice($org);

        config()->set('services.payment_gateway.webhook_secret', 'test-secret');

        $link = TenantContext::run($org['organization']->id, fn () => PaymentLink::create([
            'invoice_id' => $invoice->id,
            'provider' => 'gateway',
            'token' => 'tok_mismatch',
            'amount' => 500_000,
            'status' => 'pending',
            'url' => 'https://example.test/pay',
        ]));

        $payload = json_encode([
            'event_id' => 'evt_mismatch',
            'reference' => $link->token,
            'amount' => 999_000,
            'currency' => 'IDR',
            'status' => 'paid',
        ]);

        $response = $this->call('POST', route('webhooks.payments', 'gateway'), [], [], [], [
            'HTTP_X-Signature' => hash_hmac('sha256', $payload, 'test-secret'),
            'CONTENT_TYPE' => 'application/json',
        ], $payload);

        $response->assertStatus(422);
        $this->assertSame(0, Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->count());
    }
}