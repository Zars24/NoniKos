<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Organization;
use App\Models\RentalAgreement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceNumberGenerator
{
    /**
     * INV/{ORGSLUG}/{YYYYMM}/{SEQ}. Sequence is derived inside the caller's transaction
     * while the organization row is locked, so two concurrent runs cannot collide.
     */
    public function next(Organization $organization, Carbon $period): string
    {
        $prefix = strtoupper($organization->invoice_prefix ?: 'INV');
        $slug = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $organization->slug) ?: 'ORG', 0, 8));
        $month = $period->format('Ym');

        $count = Invoice::withoutGlobalScopes()
            ->where('organization_id', $organization->id)
            ->where('number', 'like', "{$prefix}/{$slug}/{$month}/%")
            ->count();

        $sequence = $count + 1;
        $candidate = sprintf('%s/%s/%s/%04d', $prefix, $slug, $month, $sequence);

        while (Invoice::withoutGlobalScopes()->where('number', $candidate)->exists()) {
            $sequence++;
            $candidate = sprintf('%s/%s/%s/%04d', $prefix, $slug, $month, $sequence);
        }

        return $candidate;
    }
}
