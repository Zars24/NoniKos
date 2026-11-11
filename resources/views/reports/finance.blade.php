@extends('layouts.app')

@section('title', 'Laporan keuangan')

@section('content')
<x-ui.page-header title="Laporan keuangan" subtitle="Nilai tagihan, uang diterima, tunggakan, dan arus kas dihitung terpisah.">
    <x-slot:actions>
        <button type="button" class="btn-secondary no-print" onclick="window.print()">Cetak</button>
        @can('finance.exports.create')
            <a href="{{ route('reports.finance.export', ['format' => 'csv', 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'property_id' => $propertyId]) }}" class="btn-secondary">Export CSV</a>
            <a href="{{ route('reports.finance.export', ['format' => 'xlsx', 'from' => $from->toDateString(), 'to' => $to->toDateString(), 'property_id' => $propertyId]) }}" class="btn-secondary">Export XLSX</a>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<form method="GET" class="mb-4 flex flex-col gap-2 no-print sm:flex-row">
    <label for="from" class="sr-only">Dari</label>
    <input id="from" type="date" name="from" value="{{ $from->toDateString() }}" class="input sm:max-w-[10rem]">
    <label for="to" class="sr-only">Sampai</label>
    <input id="to" type="date" name="to" value="{{ $to->toDateString() }}" class="input sm:max-w-[10rem]">
    <label for="property_id" class="sr-only">Properti</label>
    <select id="property_id" name="property_id" class="input sm:max-w-[14rem]">
        <option value="">Semua properti</option>
        @foreach ($properties as $property)
            <option value="{{ $property->id }}" @selected($propertyId === $property->id)>{{ $property->name }}</option>
        @endforeach
    </select>
    <button class="btn-secondary" type="submit">Terapkan</button>
</form>

<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
    <x-ui.stat label="Total ditagihkan" :value="\App\Support\Money::format($summary['billed'])" hint="Nilai invoice diterbitkan pada periode" />
    <x-ui.stat label="Uang diterima" :value="\App\Support\Money::format($summary['received'])" tone="success" hint="Pembayaran terkonfirmasi pada periode" />
    <x-ui.stat label="Tunggakan" :value="\App\Support\Money::format($summary['outstanding'])" :tone="$summary['outstanding'] > 0 ? 'warning' : 'neutral'" hint="Sisa tagihan periode ini" />
    <x-ui.stat label="Lewat jatuh tempo" :value="\App\Support\Money::format($summary['overdue'])" :tone="$summary['overdue'] > 0 ? 'danger' : 'neutral'" hint="Belum dibayar setelah jatuh tempo" />
    <x-ui.stat label="Pengeluaran" :value="\App\Support\Money::format($summary['expenses'])" tone="warning" hint="Biaya operasional tercatat" />
    <x-ui.stat label="Arus kas" :value="\App\Support\Money::format($summary['cash_flow'])" :tone="$summary['cash_flow'] >= 0 ? 'success' : 'danger'" hint="Diterima − pengeluaran (bukan laba)" />
</div>

<div class="mt-4 grid gap-4 lg:grid-cols-3">
    <x-ui.card title="Tren bulanan" class="lg:col-span-2">
        @php($max = max(1, collect($series)->max(fn ($m) => max($m['billed'], $m['received']))))
        <div class="flex h-48 items-end gap-3" role="img" aria-label="Grafik tagihan dan pembayaran per bulan">
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
    </x-ui.card>

    <x-ui.card title="Umur piutang">
        <ul class="space-y-2 text-sm">
            @foreach ($aging as $bucket => $value)
                <li class="flex items-center justify-between gap-2">
                    <span class="capitalize text-sage dark:text-dark-sage">{{ str_replace(['current', '>'], ['Belum jatuh tempo', 'Lebih dari '], $bucket) }} hari</span>
                    <span class="tabular font-medium">{{ \App\Support\Money::format($value) }}</span>
                </li>
            @endforeach
        </ul>
    </x-ui.card>
</div>
@endsection