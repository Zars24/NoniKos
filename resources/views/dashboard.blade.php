@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<x-ui.page-header title="Dashboard" :subtitle="'Ringkasan operasional '.now()->translatedFormat('F Y')">
    <x-slot:actions>
        @can('payments.record')
            <a href="{{ route('payments.create') }}" class="btn-primary">Catat pembayaran</a>
        @endcan
        @can('contracts.manage')
            <a href="{{ route('agreements.create') }}" class="btn-secondary">Kontrak baru</a>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <x-ui.stat label="Properti" :value="$stats['properties']" hint="Properti aktif" />
    <x-ui.stat label="Total kamar" :value="$stats['rooms']" hint="Seluruh status" />
    <x-ui.stat label="Kamar terisi" :value="$stats['occupied']" tone="brand" hint="Dari kontrak aktif" />
    <x-ui.stat label="Okupansi" :value="$stats['occupancy'].'%'" tone="success" hint="Terisi / kamar tersedia" />
</div>

<div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <x-ui.stat label="Tagihan periode ini" :value="\App\Support\Money::format($summary['billed'])" hint="Total invoice diterbitkan" />
    <x-ui.stat label="Uang diterima" :value="\App\Support\Money::format($summary['received'])" tone="success" hint="Pembayaran terkonfirmasi" />
    <x-ui.stat label="Tunggakan" :value="\App\Support\Money::format($summary['outstanding'])" :tone="$summary['outstanding'] > 0 ? 'warning' : 'neutral'" hint="Sisa tagihan belum lunas" />
    <x-ui.stat label="Lewat jatuh tempo" :value="\App\Support\Money::format($summary['overdue'])" :tone="$summary['overdue'] > 0 ? 'danger' : 'neutral'" hint="Perlu penagihan" />
</div>

<div class="mt-4 grid gap-4 lg:grid-cols-3">
    <x-ui.card title="Tren tagihan vs pembayaran" description="Nilai invoice diterbitkan dibanding pembayaran terkonfirmasi." class="lg:col-span-2">
        @php($max = max(1, collect($series)->max(fn ($m) => max($m['billed'], $m['received']))))
        <div class="flex h-48 items-end gap-3" role="img" aria-label="Grafik tren tagihan dan pembayaran enam bulan terakhir">
            @foreach ($series as $month)
                <div class="flex flex-1 flex-col items-center gap-2">
                    <div class="flex h-40 w-full items-end justify-center gap-1">
                        <div class="w-1/3 rounded-t bg-brand/70 dark:bg-dark-champagne/70" style="height: {{ max(2, round($month['billed'] / $max * 100)) }}%" title="Tagihan {{ $month['label'] }}: {{ \App\Support\Money::format($month['billed']) }}"></div>
                        <div class="w-1/3 rounded-t bg-champagne" style="height: {{ max(2, round($month['received'] / $max * 100)) }}%" title="Diterima {{ $month['label'] }}: {{ \App\Support\Money::format($month['received']) }}"></div>
                    </div>
                    <span class="text-[11px] text-sage dark:text-dark-sage">{{ $month['label'] }}</span>
                </div>
            @endforeach
        </div>
        <div class="mt-3 flex gap-4 text-xs text-sage dark:text-dark-sage">
            <span class="flex items-center gap-1"><span class="inline-block h-2 w-3 rounded bg-brand/70 dark:bg-dark-champagne/70"></span> Tagihan</span>
            <span class="flex items-center gap-1"><span class="inline-block h-2 w-3 rounded bg-champagne"></span> Diterima</span>
        </div>
    </x-ui.card>

    <x-ui.card title="Peringatan operasional">
        <ul class="space-y-3 text-sm">
            <li class="flex items-center justify-between gap-2">
                <span>Kamar kosong</span>
                <a href="{{ route('rooms.index', ['status' => 'available']) }}" class="tabular font-semibold text-brand hover:underline dark:text-dark-champagne">{{ $stats['available'] }}</a>
            </li>
            <li class="flex items-center justify-between gap-2">
                <span>Kamar perawatan</span>
                <a href="{{ route('rooms.index', ['status' => 'maintenance']) }}" class="tabular font-semibold text-warning hover:underline dark:text-dark-warning">{{ $stats['maintenance'] }}</a>
            </li>
            <li class="flex items-center justify-between gap-2">
                <span>Invoice jatuh tempo 7 hari</span>
                <a href="{{ route('invoices.index', ['status' => 'overdue']) }}" class="tabular font-semibold text-danger hover:underline dark:text-dark-danger">{{ $dueSoon->count() }}</a>
            </li>
            <li class="flex items-center justify-between gap-2">
                <span>Kontrak berakhir ≤ 60 hari</span>
                <a href="{{ route('agreements.index') }}" class="tabular font-semibold text-brand hover:underline dark:text-dark-champagne">{{ $endingAgreements->count() }}</a>
            </li>
        </ul>
    </x-ui.card>
</div>

<div class="mt-4 grid gap-4 lg:grid-cols-2">
    <x-ui.card title="Invoice mendekati jatuh tempo">
        @forelse ($dueSoon as $invoice)
            <div class="flex items-center justify-between gap-3 border-b border-stone py-3 text-sm last:border-0 dark:border-dark-border">
                <div class="min-w-0">
                    <a href="{{ route('invoices.show', $invoice) }}" class="font-medium hover:underline">{{ $invoice->number }}</a>
                    <p class="truncate text-xs text-sage dark:text-dark-sage">{{ $invoice->tenant?->full_name }} · jatuh tempo {{ $invoice->due_date->translatedFormat('d M Y') }}</p>
                </div>
                <span class="tabular font-medium">{{ \App\Support\Money::format($invoice->balance()) }}</span>
            </div>
        @empty
            <p class="text-sm text-sage dark:text-dark-sage">Tidak ada invoice mendekati jatuh tempo.</p>
        @endforelse
    </x-ui.card>

    <x-ui.card title="Pembayaran terbaru">
        @forelse ($recentPayments as $payment)
            <div class="flex items-center justify-between gap-3 border-b border-stone py-3 text-sm last:border-0 dark:border-dark-border">
                <div class="min-w-0">
                    <p class="font-medium">{{ $payment->tenant?->full_name ?? '—' }}</p>
                    <p class="truncate text-xs text-sage dark:text-dark-sage">{{ $payment->invoice?->number }} · {{ $payment->paid_at->translatedFormat('d M Y') }}</p>
                </div>
                <span class="tabular font-medium text-success dark:text-dark-success">{{ \App\Support\Money::format($payment->amount) }}</span>
            </div>
        @empty
            <p class="text-sm text-sage dark:text-dark-sage">Belum ada pembayaran.</p>
        @endforelse
    </x-ui.card>
</div>

<div class="mt-4">
    <x-ui.card title="Kontrak segera berakhir">
        @forelse ($endingAgreements as $agreement)
            <div class="flex items-center justify-between gap-3 border-b border-stone py-3 text-sm last:border-0 dark:border-dark-border">
                <div>
                    <a href="{{ route('agreements.show', $agreement) }}" class="font-medium hover:underline">{{ $agreement->tenant?->full_name }}</a>
                    <p class="text-xs text-sage dark:text-dark-sage">Kamar {{ $agreement->room?->number }} · berakhir {{ $agreement->end_date->translatedFormat('d M Y') }}</p>
                </div>
                <x-ui.badge variant="champagne" :label="$agreement->end_date->diffForHumans()" />
            </div>
        @empty
            <p class="text-sm text-sage dark:text-dark-sage">Tidak ada kontrak yang berakhir dalam 60 hari.</p>
        @endforelse
    </x-ui.card>
</div>
@endsection
