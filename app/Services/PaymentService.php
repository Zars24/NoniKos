<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(protected ActivityLogger $activity)
    {
    }

    /**
     * Record a manual (cash / transfer) payment. Amount may be partial but never exceeds the balance.
     */
    public function record(Invoice $invoice, array $data, ?User $actor = null): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $actor) {
            /** @var Invoice $locked */
            $locked = Invoice::withoutGlobalScopes()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, ['cancelled', 'draft'], true)) {
                throw ValidationException::withMessages(['amount' => 'Invoice belum diterbitkan atau sudah dibatalkan.']);
            }

            $amount = (int) $data['amount'];

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Nominal pembayaran harus lebih dari nol.']);
            }

            if ($amount > $locked->balance()) {
                throw ValidationException::withMessages([
                    'amount' => 'Nominal melebihi sisa tagihan ('.number_format($locked->balance(), 0, ',', '.').').',
                ]);
            }

            $payment = Payment::create([
                'organization_id' => $locked->organization_id,
                'invoice_id' => $locked->id,
                'tenant_id' => $locked->tenant_id,
                'reference' => $data['reference'] ?? null,
                'amount' => $amount,
                'method' => $data['method'],
                'status' => 'confirmed',
                'paid_at' => $data['paid_at'] ?? today(),
                'proof_path' => $data['proof_path'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $actor?->id,
            ]);

            $this->recalculate($locked);

            $this->activity->log('payment.recorded', $payment, 'Pembayaran diterima untuk invoice '.$locked->number, [
                'amount' => $amount,
                'method' => $payment->method,
            ]);

            return $payment;
        });
    }

    /**
     * Reverse a confirmed payment. The row is kept for audit; the invoice balance is recomputed.
     */
    public function reverse(Payment $payment, string $reason, ?User $actor = null): Payment
    {
        return DB::transaction(function () use ($payment, $reason, $actor) {
            $locked = Payment::withoutGlobalScopes()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'confirmed') {
                throw ValidationException::withMessages(['payment' => 'Hanya pembayaran terkonfirmasi yang dapat dibatalkan.']);
            }

            $locked->forceFill([
                'status' => 'reversed',
                'reversed_at' => now(),
                'reversed_by' => $actor?->id,
                'reversal_reason' => $reason,
            ])->save();

            $invoice = Invoice::withoutGlobalScopes()->whereKey($locked->invoice_id)->lockForUpdate()->firstOrFail();
            $this->recalculate($invoice);

            $this->activity->log('payment.reversed', $locked, 'Pembayaran dibatalkan untuk invoice '.$invoice->number, [
                'amount' => $locked->amount,
                'reason' => $reason,
            ]);

            return $locked;
        });
    }

    /**
     * Confirm a payment created from a verified online webhook.
     * Caller must have verified signature and amount already.
     */
    public function confirmOnline(Invoice $invoice, int $amount, string $reference, ?PaymentWebhookEvent $event = null): Payment
    {
        return DB::transaction(function () use ($invoice, $amount, $reference, $event) {
            $locked = Invoice::withoutGlobalScopes()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($amount > $locked->balance()) {
                throw ValidationException::withMessages(['amount' => 'Nominal pembayaran melebihi sisa tagihan.']);
            }

            $payment = Payment::create([
                'organization_id' => $locked->organization_id,
                'invoice_id' => $locked->id,
                'tenant_id' => $locked->tenant_id,
                'reference' => $reference,
                'amount' => $amount,
                'method' => 'online',
                'status' => 'confirmed',
                'paid_at' => today(),
            ]);

            $this->recalculate($locked);

            $this->activity->log('payment.online_confirmed', $payment, 'Pembayaran online terverifikasi untuk invoice '.$locked->number, [
                'amount' => $amount,
            ]);

            return $payment;
        });
    }

    public function recalculate(Invoice $invoice): Invoice
    {
        $paid = (int) Payment::withoutGlobalScopes()
            ->where('invoice_id', $invoice->id)
            ->where('status', 'confirmed')
            ->sum('amount');

        $invoice->forceFill(['amount_paid' => $paid]);
        $invoice->recalculateStatus();
        $invoice->save();

        return $invoice;
    }
}
