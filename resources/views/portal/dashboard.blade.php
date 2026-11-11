@extends('layouts.app')

@section('title', 'Portal saya')

@section('content')
<x-ui.page-header :title="'Halo, '.$tenant->full_name" subtitle="Ringkasan kontrak, tagihan, dan pembayaran Anda." />

<div class="grid gap-3 sm:grid-cols-3">
    <x-ui.stat label="Sisa tagihan" :value="\App\Support\Money::format($balance)" :tone="$balance > 0 ? 'danger' : 'success'" />
    <x-ui.stat label="Kamar" :value="$agreement?->room?->number ?? '—'" hint="{{ $agreement?->property?->name }}" />
    <x-ui.stat label="Kontrak berakhir" :value="$agreement?->end_date?->translatedFormat('d M Y') ?? '—'" />
</div>

@if ($agreement)
    <div class="mt-4">
        <x-ui.card title="Kontrak aktif" :description="$agreement->code">
            <dl class="grid gap-2 text-sm sm:grid-cols-2">
                <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Mulai</dt><dd>{{ $agreement->start_date->translatedFormat('d F Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Berakhir</dt><dd>{{ $agreement->end_date->translatedFormat('d F Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Sewa</dt><dd class="tabular">{{ \App\Support\Money::format($agreement->monthly_price) }}/bulan</dd></div>
                <div class="flex justify-between"><dt class="text-sage dark:text-dark-sage">Jatuh tempo</dt><dd>{{ $agreement->due_days }} hari setelah periode</dd></div>
            </dl>
        </x-ui.card>
    </div>
@else
    <div class="mt-4">
        <x-ui.alert variant="warning">Anda belum memiliki kontrak sewa aktif. Hubungi pengelola bila ini keliru.</x-ui.alert>
    </div>
@endif

<div class="mt-4 grid gap-4 lg:grid-cols-2">
    <x-ui.card title="Tagihan terbaru">
        @forelse ($invoices as $invoice)
            <div class="flex items-center justify-between gap-3 border-b border-stone py-3 text-sm last:border-0 dark:border-dark-border">
                <div>
                    <a href="{{ route('portal.invoices.show', $invoice) }}" class="font-medium hover:underline">{{ $invoice->number }}</a>
                    <p class="text-xs text-sage dark:text-dark-sage">Jatuh tempo {{ $invoice->due_date->translatedFormat('d M Y') }}</p>
                </div>
                <div class="text-right">
                    <p class="tabular">{{ \App\Support\Money::format($invoice->balance()) }}</p>
                    <x-ui.status-badge :status="$invoice->status" />
                </div>
            </div>
        @empty
            <p class="text-sm text-sage dark:text-dark-sage">Belum ada tagihan.</p>
        @endforelse
    </x-ui.card>

    <x-ui.card title="Pembayaran terbaru">
        @forelse ($payments as $payment)
            <div class="flex items-center justify-between gap-3 border-b border-stone py-3 text-sm last:border-0 dark:border-dark-border">
                <div>
                    <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</p>
                    <p class="text-xs text-sage dark:text-dark-sage">{{ $payment->paid_at->translatedFormat('d M Y') }}</p>
                </div>
                <span class="tabular text-success dark:text-dark-success">{{ \App\Support\Money::format($payment->amount) }}</span>
            </div>
        @empty
            <p class="text-sm text-sage dark:text-dark-sage">Belum ada pembayaran.</p>
        @endforelse
    </x-ui.card>
</div>
@endsection