@extends('layouts.app')

@section('title', 'Penyewa')

@section('content')
<x-ui.page-header title="Penyewa" subtitle="Data penyewa, riwayat sewa, dan tagihan dalam satu tempat.">
    <x-slot:actions>
        @can('tenants.import')
            <a href="{{ route('tenants.import.template') }}" class="btn-secondary">Template CSV</a>
        @endcan
        @can('tenants.manage')
            <a href="{{ route('tenants.create') }}" class="btn-primary">+ Penyewa baru</a>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
    <label for="q" class="sr-only">Cari penyewa</label>
    <input id="q" name="q" value="{{ request('q') }}" placeholder="Cari nama, telepon, email…" class="input sm:max-w-xs">
    <label for="status" class="sr-only">Status</label>
    <select id="status" name="status" class="input sm:max-w-[10rem]">
        <option value="">Semua status</option>
        <option value="active" @selected(request('status') === 'active')>Aktif</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
    </select>
    <button class="btn-secondary" type="submit">Filter</button>
</form>

@can('tenants.import')
    <x-ui.card class="mb-4" title="Import penyewa (CSV)">
        <form method="POST" action="{{ route('tenants.import') }}" enctype="multipart/form-data" class="flex flex-col gap-2 sm:flex-row sm:items-end">
            @csrf
            <div class="flex-1">
                <label for="file" class="label">Berkas CSV</label>
                <input id="file" type="file" name="file" accept=".csv,text/csv" required class="input mt-1 file:mr-3 file:rounded file:border-0 file:bg-brand file:px-3 file:py-1.5 file:text-white">
                @error('file')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-secondary">Validasi & import</button>
        </form>
        <p class="hint mt-2">Semua baris divalidasi lebih dahulu; import dibatalkan bila ada baris tidak valid.</p>
    </x-ui.card>
@endcan

@if ($tenants->isEmpty())
    <x-ui.empty-state title="Belum ada penyewa" description="Tambahkan penyewa atau import dari CSV." />
@else
    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th scope="col">Nama</th>
                        <th scope="col">Kontak</th>
                        <th scope="col">Kamar aktif</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tenants as $tenant)
                        <tr>
                            <td>
                                <p class="font-medium text-ink dark:text-dark-ink">{{ $tenant->full_name }}</p>
                                <p class="text-xs text-sage dark:text-dark-sage">{{ $tenant->code }}</p>
                            </td>
                            <td>
                                <p>{{ $tenant->phone ?: '—' }}</p>
                                <p class="text-xs text-sage dark:text-dark-sage">{{ $tenant->email ?: '—' }}</p>
                            </td>
                            <td>
                                @php($agreement = $tenant->agreements->first())
                                {{ $agreement?->room?->number ?? '—' }}
                            </td>
                            <td><x-ui.status-badge :status="$tenant->status" /></td>
                            <td class="text-right">
                                <a href="{{ route('tenants.show', $tenant) }}" class="btn-ghost px-2 py-1">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.pagination :paginator="$tenants" />
@endif
@endsection
