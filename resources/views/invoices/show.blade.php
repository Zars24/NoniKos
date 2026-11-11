@extends('layouts.app')

@section('title', $invoice->number)

@section('content')
<x-ui.page-header :title="$invoice->number" :subtitle="$invoice->tenant?->full_name.' · '.$invoice->property?->name">
    <x-slot:actions>
        <a href="{{ route('invoices.pdf', $invoice) }}" class="btn-secondary">Unduh PDF</a>
        @can('invoices.adjust')
            @if ($invoice->status === 'draft')
                <form method="POST" action="{{ route('invoices.issue', $invoice) }}" class="inline">
                    @csrf
                    <button class="btn-primary">Terbitkan</button>
                </form>
            @endif
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<div class="grid gap-4 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        <x-ui.card title="Item invoice">
            <div class="overflow-x-auto">
                <table class="table-base">
                    <thead>
                        <tr>
                            <th scope="col">Deskripsi</th>
                            <th scope="col" class="text-right">Qty</th>
                            <th scope="col" class="text-right">Harga</th>
                            <th scope="col" class="text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->items as $item)
                            <tr>
                                <td>
                                    {{ $item->description }}
                                    <span class="ml-1 text-xs text-sage dark:text-dark-sage">{{ $item->type }}</span>
                                </td>
                                <td class="tabular text-right">{{ $item->quantity }}</td>
                                <td class="tabular text-right">{{ \App\Support\Money::format($item->unit_price) }}</td>
                                <td class="tabular text-right">{{ \App\Support\Money::format($item->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <dl class="mt-4 space-y-1 border-t border-stone pt-4 text-sm dark:border-dark-border">
                <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Subtotal</dt><dd class="tabular">{{ \App\Support\Money::format($invoice->subtotal) }}</dd></div>
                @if ($invoice->discount_total > 0)
                    <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Diskon</dt><dd class="tabular">−{{ \App\Support\Money::format($invoice->discount_total) }}</dd></div>
                @endif
                @if ($invoice->late_fee_total > 0)
                    <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Denda</dt><dd class="tabular">{{ \App\Support\Money::format($invoice->late_fee_total) }}</dd></div>
                @endif
                <div class="flex justify-between border-t border-stone pt-2 font-semibold dark:border-dark-border"><dt>Total</dt><dd class="tabular">{{ \App\Support\Money::format($invoice->total) }}</dd></div>
                <div class="flex justify-between text-success dark:text-dark-success"><dt>Dibayar</dt><dd class="tabular">{{ \App\Support\Money::format($invoice->amount_paid) }}</dd></div>
                <div class="flex justify-between font-semibold"><dt>Sisa tagihan</dt><dd class="tabular">{{ \App\Support\Money::format($invoice->balance()) }}</dd></div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Pembayaran">
            @forelse ($invoice->payments as $payment)
                <div class="flex items-center justify-between gap-3 border-b border-stone py-3 text-sm last:border-0 dark:border-dark-border">
                    <div>
                        <a href="{{ route('payments.show', $payment) }}" class="font-medium hover:underline">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</a>
                        <p class="text-xs text-sage dark:text-dark-sage">
                            {{ $payment->paid_at->translatedFormat('d M Y') }}
                            @if ($payment->recordedBy) · dicatat {{ $payment->recordedBy->name }} @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="tabular">{{ \App\Support\Money::format($payment->amount) }}</span>
                        <x-ui.status-badge :status="$payment->status" />
                    </div>
                </div>
            @empty
                <p class="text-sm text-sage dark:text-dark-sage">Belum ada pembayaran.</p>
            @endforelse

            @can('payments.record')
                @if (in_array($invoice->status, ['issued', 'partially_paid', 'overdue'], true))
                    <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="btn-primary mt-3 w-full">Catat pembayaran</a>
                @endif
            @endcan
        </x-ui.card>
    </div>

    <div class="space-y-4">
        <x-ui.card title="Status">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Status</dt><dd><x-ui.status-badge :status="$invoice->status" /></dd></div>
                <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Terbit</dt><dd>{{ $invoice->issued_at?->translatedFormat('d M Y') ?? '—' }}</dd></div>
                <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Jatuh tempo</dt><dd>{{ $invoice->due_date->translatedFormat('d M Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Kontrak</dt><dd><a href="{{ route('agreements.show', $invoice->rental_agreement_id) }}" class="hover:underline">{{ $invoice->agreement?->code }}</a></dd></div>
                <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Kamar</dt><dd>{{ $invoice->agreement?->room?->number ?? '—' }}</dd></div>
            </dl>
        </x-ui.card>

        @can('invoices.adjust')
            <x-ui.card title="Tindakan">
                @if ($invoice->status !== 'draft' && $invoice->status !== 'cancelled')
                    <form method="POST" action="{{ route('invoices.reminder', $invoice) }}" class="mb-2">
                        @csrf
                        <button class="btn-secondary w-full">Kirim pengingat WhatsApp</button>
                    </form>

                    <form method="POST" action="{{ route('invoices.late-fee', $invoice) }}" class="mb-2">
                        @csrf
                        <button class="btn-secondary w-full">Terapkan denda keterlambatan</button>
                    </form>

                    <form method="POST" action="{{ route('invoices.payment-link', $invoice) }}" class="mb-2">
                        @csrf
                        <button class="btn-secondary w-full">Buat tautan pembayaran</button>
                    </form>
                @endif

                @can('invoices.cancel')
                    @if (! in_array($invoice->status, ['cancelled', 'paid'], true))
                        <x-ui.delete-form :action="route('invoices.cancel', $invoice)" method="POST" variant="danger" label="Batalkan invoice" confirm="Batalkan invoice ini?" />
                    @endif
                @endcan

                @if ($invoice->status === 'draft')
                    <a href="{{ route('invoices.edit', $invoice) }}" class="mt-2 block w-full text-center btn-ghost">Sunting draf</a>
                @endif
            </x-ui.card>

            <x-ui.card title="Penyesuaian keuangan">
                <form method="POST" action="{{ route('invoices.adjustments.store', $invoice) }}" class="space-y-3">
                    @csrf
                    <x-ui.select label="Jenis" name="type" required
                        :options="['invoice_correction' => 'Koreksi invoice', 'deposit_refund' => 'Pengembalian deposit', 'credit' => 'Kredit', 'write_off' => 'Penghapusan piutang']" />
                    <x-ui.input label="Nominal" name="amount" required help="Contoh: 100.000" />
                    <x-ui.input label="Alasan" name="reason" required />
                    <button class="btn-secondary w-full">Catat penyesuaian</button>
                </form>
            </x-ui.card>
        @endcan

        @if ($invoice->paymentLinks->isNotEmpty())
            <x-ui.card title="Tautan pembayaran">
                <ul class="space-y-2 text-sm">
                    @foreach ($invoice->paymentLinks as $link)
                        <li class="flex items-center justify-between gap-2">
                            <a href="{{ $link->url }}" class="truncate text-xs hover:underline" target="_blank" rel="noopener">{{ $link->token }}</a>
                            <x-ui.status-badge :status="$link->status" />
                        </li>
                    @endforeach
                </ul>
            </x-ui.card>
        @endif
    </div>
</div>
@endsection