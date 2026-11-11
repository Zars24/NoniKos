@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
<x-ui.page-header title="Pembayaran" subtitle="Hanya pembayaran terkonfirmasi yang mengurangi saldo tagihan.">
    <x-slot:actions>
        @can('payments.record')
            <a href="{{ route('payments.create') }}" class="btn-primary">+ Pembayaran</a>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
    <label for="status" class="sr-only">Status</label>
    <select id="status" name="status" class="input sm:max-w-[12rem]">
        <option value="">Semua status</option>
        @foreach (\App\Models\Payment::STATUSES as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <label for="method" class="sr-only">Metode</label>
    <select id="method" name="method" class="input sm:max-w-[12rem]">
        <option value="">Semua metode</option>
        @foreach (\App\Models\Payment::METHODS as $method)
            <option value="{{ $method }}" @selected(request('method') === $method)>{{ ucfirst(str_replace('_', ' ', $method)) }}</option>
        @endforeach
    </select>
    <label for="from" class="sr-only">Dari</label>
    <input id="from" type="date" name="from" value="{{ request('from') }}" class="input sm:max-w-[10rem]">
    <label for="to" class="sr-only">Sampai</label>
    <input id="to" type="date" name="to" value="{{ request('to') }}" class="input sm:max-w-[10rem]">
    <button class="btn-secondary" type="submit">Filter</button>
</form>

@if ($payments->isEmpty())
    <x-ui.empty-state title="Belum ada pembayaran" description="Catat pembayaran tunai atau transfer untuk mengurangi saldo invoice." />
@else
    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th scope="col">Tanggal</th>
                        <th scope="col">Penyewa</th>
                        <th scope="col">Invoice</th>
                        <th scope="col">Metode</th>
                        <th scope="col" class="text-right">Nominal</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at->translatedFormat('d M Y') }}</td>
                            <td>{{ $payment->tenant?->full_name }}</td>
                            <td class="text-xs">{{ $payment->invoice?->number }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
                            <td class="tabular text-right">{{ \App\Support\Money::format($payment->amount) }}</td>
                            <td><x-ui.status-badge :status="$payment->status" /></td>
                            <td class="text-right">
                                <a href="{{ route('payments.show', $payment) }}" class="btn-ghost px-2 py-1">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.pagination :paginator="$payments" />
@endif
@endsection