<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToOrganization, HasFactory;

    public const STATUSES = ['draft', 'issued', 'partially_paid', 'paid', 'overdue', 'cancelled'];

    protected $fillable = [
        'organization_id',
        'property_id',
        'tenant_id',
        'rental_agreement_id',
        'number',
        'period_start',
        'period_end',
        'issue_date',
        'due_date',
        'subtotal',
        'discount_total',
        'late_fee_total',
        'total',
        'amount_paid',
        'status',
        'issued_at',
        'paid_at',
        'late_fee_applied_at',
        'notes',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'issue_date' => 'date',
        'due_date' => 'date',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
        'late_fee_applied_at' => 'datetime',
        'subtotal' => 'integer',
        'discount_total' => 'integer',
        'late_fee_total' => 'integer',
        'total' => 'integer',
        'amount_paid' => 'integer',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(RentalAgreement::class, 'rental_agreement_id');
    }

    public function paymentLinks(): HasMany
    {
        return $this->hasMany(PaymentLink::class);
    }

    public function balance(): int
    {
        return max(0, $this->total - $this->amount_paid);
    }

    public function isOverdue(): bool
    {
        return $this->balance() > 0
            && ! in_array($this->status, ['draft', 'cancelled', 'paid'], true)
            && $this->due_date->lt(today());
    }

    public function recalculateStatus(): void
    {
        if (in_array($this->status, ['draft', 'cancelled'], true)) {
            return;
        }

        if ($this->total > 0 && $this->amount_paid >= $this->total) {
            $this->status = 'paid';
            $this->paid_at ??= now();
        } elseif ($this->amount_paid > 0) {
            $this->status = $this->isOverdue() ? 'overdue' : 'partially_paid';
            $this->paid_at = null;
        } else {
            $this->status = $this->isOverdue() ? 'overdue' : 'issued';
            $this->paid_at = null;
        }
    }
}
