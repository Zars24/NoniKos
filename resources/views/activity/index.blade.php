@extends('layouts.app')

@section('title', 'Log aktivitas')

@section('content')
<x-ui.page-header title="Log aktivitas" subtitle="Catatan audit tidak dapat diedit atau dihapus dari antarmuka." />

<form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
    <label for="action" class="sr-only">Aksi</label>
    <input id="action" name="action" value="{{ request('action') }}" placeholder="Cari aksi, mis. invoice" class="input sm:max-w-xs">
    <label for="from" class="sr-only">Dari</label>
    <input id="from" type="date" name="from" value="{{ request('from') }}" class="input sm:max-w-[10rem]">
    <label for="to" class="sr-only">Sampai</label>
    <input id="to" type="date" name="to" value="{{ request('to') }}" class="input sm:max-w-[10rem]">
    <button class="btn-secondary" type="submit">Filter</button>
</form>

@if ($logs->isEmpty())
    <x-ui.empty-state title="Belum ada aktivitas" />
@else
    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr><th scope="col">Waktu</th><th scope="col">Aktor</th><th scope="col">Aksi</th><th scope="col">Deskripsi</th><th scope="col">IP</th></tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td class="whitespace-nowrap text-xs">{{ $log->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td>{{ $log->user?->name ?? 'Sistem' }}</td>
                            <td><span class="font-mono text-xs">{{ $log->action }}</span></td>
                            <td>{{ $log->description }}</td>
                            <td class="text-xs text-sage">{{ $log->ip_address }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.pagination :paginator="$logs" />
@endif
@endsection