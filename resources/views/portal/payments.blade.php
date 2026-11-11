@extends('layouts.app')

@section('title', 'Pembayaran saya')

@section('content')
<x-ui.page-header title="Pembayaran saya" />

@if ($payments->isEmpty())
    <x-ui.empty-state title="Belum ada pembayaran" />
@else
    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr><th scope="col">Tanggal</th><th scope="col">Metode</th><th scope="col" class="text-right">Nominal</th><th scope="col">Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at->translatedFormat('d M Y') }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td>
                            <td class="tabular text-right">{{ \App\Support\Money::format($payment->amount) }}</td>
                            <td><x-ui.status-badge :status="$payment->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.pagination :paginator="$payments" />
@endif
@endsection