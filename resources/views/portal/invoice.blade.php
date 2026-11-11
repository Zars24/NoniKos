@extends('layouts.app')

@section('title', $invoice->number)

@section('content')
<x-ui.page-header :title="$invoice->number" subtitle="Rincian tagihan Anda">
    <x-slot:actions>
        <a href="{{ route('portal.invoices.pdf', $invoice) }}" class="btn-secondary">Unduh PDF</a>
    </x-slot:actions>
</x-ui.page-header>

<div class="grid gap-4 lg:grid-cols-3">
    <x-ui.card title="Item" class="lg:col-span-2">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr><th scope="col">Deskripsi</th><th scope="col" class="text-right">Jumlah</th></tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td class="tabular text-right">{{ \App\Support\Money::format($item->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <dl class="mt-4 space-y-1 border-t border-stone pt-4 text-sm dark:border-dark-border">
            <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Total</dt><dd class="tabular">{{ \App\Support\Money::format($invoice->total) }}</dd></div>
            <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Dibayar</dt><dd class="tabular">{{ \App\Support\Money::format($invoice->amount_paid) }}</dd></div>
            <div class="flex justify-between font-semibold"><dt>Sisa</dt><dd class="tabular">{{ \App\Support\Money::format($invoice->balance()) }}</dd></div>
        </dl>
    </x-ui.card>

    <div class="space-y-4">
        <x-ui.card title="Status">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Status</dt><dd><x-ui.status-badge :status="$invoice->status" /></dd></div>
                <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Jatuh tempo</dt><dd>{{ $invoice->due_date->translatedFormat('d F Y') }}</dd></div>
            </dl>
        </x-ui.card>

        @if ($invoice->balance() > 0 && ! in_array($invoice->status, ['draft', 'cancelled'], true))
            <x-ui.card title="Pembayaran">
                <p class="text-sm text-sage dark:text-dark-sage">Buat tautan pembayaran untuk menyelesaikan tagihan ini.</p>
                <form method="POST" action="{{ route('portal.invoices.pay', $invoice) }}" class="mt-3">
                    @csrf
                    <button class="btn-primary w-full">Bayar sekarang</button>
                </form>
            </x-ui.card>
        @endif

        @if ($invoice->payments->isNotEmpty())
            <x-ui.card title="Riwayat pembayaran">
                @foreach ($invoice->payments as $payment)
                    <div class="flex items-center justify-between gap-2 border-b border-stone py-2 text-sm last:border-0 dark:border-dark-border">
                        <span>{{ $payment->paid_at->translatedFormat('d M Y') }}</span>
                        <span class="tabular">{{ \App\Support\Money::format($payment->amount) }}</span>
                    </div>
                @endforeach
            </x-ui.card>
        @endif
    </div>
</div>
@endsection