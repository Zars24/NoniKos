@extends('layouts.app')

@section('title', 'Detail pembayaran')

@section('content')
<x-ui.page-header :title="'Pembayaran '.\App\Support\Money::format($payment->amount)" :subtitle="$payment->tenant?->full_name">
    <x-slot:actions>
        <a href="{{ route('payments.receipt', $payment) }}" class="btn-secondary">Unduh kuitansi</a>
    </x-slot:actions>
</x-ui.page-header>

<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.card title="Ringkasan">
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Status</dt><dd><x-ui.status-badge :status="$payment->status" /></dd></div>
            <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Invoice</dt><dd><a href="{{ route('invoices.show', $payment->invoice_id) }}" class="hover:underline">{{ $payment->invoice?->number }}</a></dd></div>
            <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Metode</dt><dd>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</dd></div>
            <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Tanggal bayar</dt><dd>{{ $payment->paid_at->translatedFormat('d M Y') }}</dd></div>
            <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Referensi</dt><dd>{{ $payment->reference ?: '—' }}</dd></div>
            <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Dicatat oleh</dt><dd>{{ $payment->recordedBy?->name ?? 'Sistem' }}</dd></div>
            @if ($payment->reversed_at)
                <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Dibatalkan</dt><dd>{{ $payment->reversed_at->translatedFormat('d M Y H:i') }}</dd></div>
                <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Alasan</dt><dd>{{ $payment->reversal_reason }}</dd></div>
            @endif
        </dl>
        @if ($payment->notes)
            <p class="mt-3 border-t border-stone pt-3 text-sm text-sage dark:border-dark-border dark:text-dark-sage">{{ $payment->notes }}</p>
        @endif
    </x-ui.card>

    @can('payments.refund')
        @if ($payment->status === 'confirmed')
            <x-ui.card title="Pembalikan pembayaran">
                <p class="text-sm text-sage dark:text-dark-sage">
                    Pembalikan tidak menghapus data; status menjadi <strong>dibatalkan</strong> dan saldo invoice dihitung ulang.
                </p>
                <form method="POST" action="{{ route('payments.reverse', $payment) }}" class="mt-3 space-y-3" onsubmit="return confirm('Batalkan pembayaran ini?')">
                    @csrf
                    <x-ui.input label="Alasan pembalikan" name="reason" required />
                    <button class="btn-danger w-full">Batalkan pembayaran</button>
                </form>
            </x-ui.card>
        @endif
    @endcan
</div>
@endsection