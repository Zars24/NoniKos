@extends('layouts.app')

@section('title', 'Tagihan saya')

@section('content')
<x-ui.page-header title="Tagihan saya" subtitle="Hanya tagihan milik akun Anda yang ditampilkan." />

@if ($invoices->isEmpty())
    <x-ui.empty-state title="Belum ada tagihan" description="Tagihan akan muncul di sini setelah diterbitkan pengelola." />
@else
    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th scope="col">Invoice</th>
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
                            <td class="font-medium">{{ $invoice->number }}</td>
                            <td class="text-xs">{{ $invoice->period_start->translatedFormat('d M Y') }} – {{ $invoice->period_end->translatedFormat('d M Y') }}</td>
                            <td class="tabular text-right">{{ \App\Support\Money::format($invoice->total) }}</td>
                            <td class="tabular text-right">{{ \App\Support\Money::format($invoice->balance()) }}</td>
                            <td><x-ui.status-badge :status="$invoice->status" /></td>
                            <td class="text-right">
                                <a href="{{ route('portal.invoices.show', $invoice) }}" class="btn-ghost px-2 py-1">Detail</a>
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