@extends('layouts.app')

@section('title', 'Kamar')

@section('content')
<x-ui.page-header title="Kamar" subtitle="Status kamar mengikuti kontrak aktif, bukan input manual yang bertentangan.">
    <x-slot:actions>
        @can('rooms.manage')
            <a href="{{ route('rooms.create') }}" class="btn-primary">+ Kamar baru</a>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
    <label for="q" class="sr-only">Cari kamar</label>
    <input id="q" name="q" value="{{ request('q') }}" placeholder="Cari nomor kamar…" class="input sm:max-w-xs">
    <label for="property_id" class="sr-only">Properti</label>
    <select id="property_id" name="property_id" class="input sm:max-w-[14rem]">
        <option value="">Semua properti</option>
        @foreach ($properties as $property)
            <option value="{{ $property->id }}" @selected((string) request('property_id') === (string) $property->id)>{{ $property->name }}</option>
        @endforeach
    </select>
    <label for="status" class="sr-only">Status</label>
    <select id="status" name="status" class="input sm:max-w-[12rem]">
        <option value="">Semua status</option>
        @foreach (\App\Models\Room::STATUSES as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <button class="btn-secondary" type="submit">Filter</button>
</form>

@if ($rooms->isEmpty())
    <x-ui.empty-state title="Belum ada kamar" description="Tambahkan kamar untuk mulai membuat kontrak dan tagihan." />
@else
    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th scope="col">Kamar</th>
                        <th scope="col">Properti</th>
                        <th scope="col">Tipe</th>
                        <th scope="col" class="text-right">Harga / bulan</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rooms as $room)
                        <tr>
                            <td>
                                <p class="font-medium text-ink dark:text-dark-ink">{{ $room->number }}</p>
                                <p class="text-xs text-sage dark:text-dark-sage">
                                    {{ $room->building?->name ?? 'Tanpa gedung' }}@if($room->floor !== null) · Lantai {{ $room->floor }}@endif
                                </p>
                            </td>
                            <td>{{ $room->property?->name }}</td>
                            <td>{{ $room->roomType?->name ?? '—' }}</td>
                            <td class="tabular text-right">{{ \App\Support\Money::format($room->monthly_price) }}</td>
                            <td><x-ui.status-badge :status="$room->status" /></td>
                            <td class="text-right">
                                @can('rooms.manage')
                                    <a href="{{ route('rooms.edit', $room) }}" class="btn-ghost px-2 py-1">Kelola</a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.pagination :paginator="$rooms" />
@endif
@endsection
