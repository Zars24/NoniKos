@extends('layouts.app')

@section('title', 'Booking')

@section('content')
<x-ui.page-header title="Booking" subtitle="Booking dikonversi menjadi kontrak setelah dikonfirmasi.">
    <x-slot:actions>
        @can('contracts.manage')
            <a href="{{ route('bookings.create') }}" class="btn-primary">+ Booking</a>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<form method="GET" class="mb-4 flex gap-2">
    <label for="status" class="sr-only">Status</label>
    <select id="status" name="status" class="input sm:max-w-[12rem]" onchange="this.form.submit()">
        <option value="">Semua status</option>
        @foreach (\App\Models\Booking::STATUSES as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
</form>

@if ($bookings->isEmpty())
    <x-ui.empty-state title="Belum ada booking" description="Booking membantu menandai kamar yang akan segera terisi." />
@else
    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th scope="col">Kode</th>
                        <th scope="col">Penyewa</th>
                        <th scope="col">Kamar</th>
                        <th scope="col">Mulai</th>
                        <th scope="col" class="text-right">Harga</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bookings as $booking)
                        <tr>
                            <td class="font-medium">{{ $booking->code }}</td>
                            <td>{{ $booking->tenant?->full_name }}</td>
                            <td>{{ $booking->room?->number }}<span class="text-xs text-sage dark:text-dark-sage"> · {{ $booking->property?->name }}</span></td>
                            <td>{{ $booking->start_date->translatedFormat('d M Y') }}</td>
                            <td class="tabular text-right">{{ \App\Support\Money::format($booking->monthly_price) }}</td>
                            <td><x-ui.status-badge :status="$booking->status" /></td>
                            <td class="text-right">
                                @can('contracts.manage')
                                    <div class="flex justify-end gap-1">
                                        @if ($booking->status === 'pending')
                                            <form method="POST" action="{{ route('bookings.confirm', $booking) }}">@csrf<button class="btn-ghost px-2 py-1">Konfirmasi</button></form>
                                        @endif
                                        @if (in_array($booking->status, ['pending', 'confirmed'], true))
                                            <form method="POST" action="{{ route('bookings.convert', $booking) }}" onsubmit="return confirm('Konversi booking ini menjadi kontrak?')">@csrf<button class="btn-ghost px-2 py-1">Konversi</button></form>
                                            <form method="POST" action="{{ route('bookings.cancel', $booking) }}" onsubmit="return confirm('Batalkan booking ini?')">@csrf<button class="btn-ghost px-2 py-1 text-danger">Batal</button></form>
                                        @endif
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.pagination :paginator="$bookings" />
@endif
@endsection