@extends('layouts.app')

@section('title', 'Pemeliharaan')

@section('content')
<x-ui.page-header title="Pemeliharaan" subtitle="Kelola laporan kerusakan dan penugasan staf.">
    <x-slot:actions>
        @can('maintenance.manage')
            <a href="{{ route('maintenance.create') }}" class="btn-primary">+ Laporan</a>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<form method="GET" class="mb-4 flex gap-2">
    <label for="status" class="sr-only">Status</label>
    <select id="status" name="status" class="input sm:max-w-[12rem]">
        <option value="">Semua status</option>
        @foreach (\App\Models\MaintenanceRequest::STATUSES as $status)
            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
        @endforeach
    </select>
    <label for="priority" class="sr-only">Prioritas</label>
    <select id="priority" name="priority" class="input sm:max-w-[12rem]">
        <option value="">Semua prioritas</option>
        @foreach (\App\Models\MaintenanceRequest::PRIORITIES as $priority)
            <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>
        @endforeach
    </select>
    <button class="btn-secondary" type="submit">Filter</button>
</form>

@if ($requests->isEmpty())
    <x-ui.empty-state title="Belum ada laporan" description="Laporan dari penyewa atau staf akan muncul di sini." />
@else
    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th scope="col">Judul</th>
                        <th scope="col">Lokasi</th>
                        <th scope="col">Prioritas</th>
                        <th scope="col">Petugas</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        <tr>
                            <td>
                                <p class="font-medium">{{ $request->title }}</p>
                                <p class="text-xs text-sage dark:text-dark-sage">{{ ucfirst($request->category) }} · {{ $request->created_at->translatedFormat('d M Y') }}</p>
                            </td>
                            <td>{{ $request->property?->name }}<span class="text-xs text-sage dark:text-dark-sage">@if($request->room) · Kamar {{ $request->room->number }}@endif</span></td>
                            <td><x-ui.badge :variant="in_array($request->priority, ['urgent','high']) ? 'danger' : ($request->priority === 'normal' ? 'info' : 'muted')" :label="ucfirst($request->priority)" /></td>
                            <td>{{ $request->assignee?->name ?? '—' }}</td>
                            <td><x-ui.status-badge :status="$request->status" /></td>
                            <td class="text-right">
                                <a href="{{ route('maintenance.show', $request) }}" class="btn-ghost px-2 py-1">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.pagination :paginator="$requests" />
@endif
@endsection