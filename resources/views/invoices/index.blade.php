@extends('layouts.app')

@section('title', 'Invoice')

@section('content')
<x-ui.page-header title="Invoice" subtitle="Nilai invoice, pembayaran diterima, dan sisa tagihan dipisahkan dengan jelas.">
    <x-slot:actions>
        @can('invoices.create')
            <a href="{{ route('invoices.create') }}" class="btn-primary">+ Invoice</a>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
    <label for="q" class="sr-only">Cari invoice</label>
    <input id="q" name="q" value="{{ request('q') }}" placeholder="Nomor invoice atau nama penyewa…" class="input sm:max-w-xs">
    <label for="status" class="sr-only">Status</label>
    <select id="status" name="status" class="input sm:max-w-[12rem]">
        <option value="">Semua status</option>
        @foreach (\App\Models\Invoice::STATUSES as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
        @endforeach
    </select>
    <label for="from" class="sr-only">Dari</label>
    <input id="from" type="date" name="from" value="{{ request('from') }}" class="input sm:max-w-[10rem]">
    <label for="to" class="sr-only">Sampai</label>
    <input id="to" type="date" name="to" value="{{ request('to') }}" class="input sm:max-w-[10rem]">
    <button class="btn-secondary" type="submit">Filter</button>
</form>

@if ($invoices->isEmpty())
    <x-ui.empty-state title="Belum ada invoice" description="Invoice dibuat dari kontrak aktif melalui penagihan berulang." />
@else
    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th scope="col">Invoice</th>
                        <th scope="col">Penyewa</th>
                        <th scope="col">Periode</th>
                        <th scope="col" class="text-right">Total</th>
                        <th scope="col" class="text-right">Sisa</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoices as $invoice)
                        <tr>
                            <td>
                                <p class="font-medium text-ink dark:text-dark-ink">{{ $invoice->number }}</p>
                                <p class="text-xs text-sage dark:text-dark-sage">Jatuh tempo {{ $invoice->due_date->translatedFormat('d M Y') }}</p>
                            </td>
                            <td>{{ $invoice->tenant?->full_name }}</td>
                            <td class="text-xs">{{ $invoice->period_start->translatedFormat('d M Y') }} – {{ $invoice->period_end->translatedFormat('d M Y') }}</td>
                            <td class="tabular text-right">{{ \App\Support\Money::format($invoice->total) }}</td>
                            <td class="tabular text-right {{ $invoice->balance() > 0 ? 'text-danger dark:text-dark-danger' : 'text-success dark:text-dark-success' }}">{{ \App\Support\Money::format($invoice->balance()) }}</td>
                            <td><x-ui.status-badge :status="$invoice->status" /></td>
                            <td class="text-right">
                                <a href="{{ route('invoices.show', $invoice) }}" class="btn-ghost px-2 py-1">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.pagination :paginator="$invoices" />
@endif
@endsection