@extends('layouts.app')

@section('title', 'Kontrak sewa')

@section('content')
<x-ui.page-header title="Kontrak sewa" subtitle="Satu kamar hanya boleh memiliki satu kontrak aktif pada waktu yang sama.">
    <x-slot:actions>
        @can('contracts.manage')
            <a href="{{ route('agreements.create') }}" class="btn-primary">+ Kontrak</a>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
    <label for="q" class="sr-only">Cari kontrak</label>
    <input id="q" name="q" value="{{ request('q') }}" placeholder="Kode kontrak atau nama penyewa…" class="input sm:max-w-xs">
    <label for="status" class="sr-only">Status</label>
    <select id="status" name="status" class="input sm:max-w-[12rem]">
        <option value="">Semua status</option>
        @foreach (\App\Models\RentalAgreement::STATUSES as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <button class="btn-secondary" type="submit">Filter</button>
</form>

@if ($agreements->isEmpty())
    <x-ui.empty-state title="Belum ada kontrak" description="Buat kontrak dari booking atau langsung dari menu ini." />
@else
    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th scope="col">Kode</th>
                        <th scope="col">Penyewa</th>
                        <th scope="col">Kamar</th>
                        <th scope="col">Periode</th>
                        <th scope="col" class="text-right">Sewa / bulan</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($agreements as $agreement)
                        <tr>
                            <td class="font-medium">{{ $agreement->code }}</td>
                            <td>{{ $agreement->tenant?->full_name }}</td>
                            <td>{{ $agreement->room?->number }}<span class="text-xs text-sage dark:text-dark-sage"> · {{ $agreement->property?->name }}</span></td>
                            <td class="text-xs">{{ $agreement->start_date->translatedFormat('d M Y') }} – {{ $agreement->end_date->translatedFormat('d M Y') }}</td>
                            <td class="tabular text-right">{{ \App\Support\Money::format($agreement->monthly_price) }}</td>
                            <td><x-ui.status-badge :status="$agreement->status" /></td>
                            <td class="text-right">
                                <a href="{{ route('agreements.show', $agreement) }}" class="btn-ghost px-2 py-1">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.pagination :paginator="$agreements" />
@endif
@endsection