@extends('layouts.app')

@section('title', $maintenance->title)

@section('content')
<x-ui.page-header :title="$maintenance->title" :subtitle="$maintenance->property?->name.' · '.ucfirst($maintenance->category)">
    <x-slot:actions>
        @can('maintenance.manage')
            <a href="{{ route('maintenance.edit', $maintenance) }}" class="btn-secondary">Edit</a>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<div class="grid gap-4 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        <x-ui.card title="Detail">
            <dl class="grid gap-2 text-sm sm:grid-cols-2">
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Status</dt><dd class="sm:mt-1"><x-ui.status-badge :status="$maintenance->status" /></dd></div>
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Prioritas</dt><dd class="sm:mt-1">{{ ucfirst($maintenance->priority) }}</dd></div>
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Kamar</dt><dd class="sm:mt-1">{{ $maintenance->room?->number ?? '—' }}</dd></div>
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Pelapor</dt><dd class="sm:mt-1">{{ $maintenance->tenant?->full_name ?? '—' }}</dd></div>
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Petugas</dt><dd class="sm:mt-1">{{ $maintenance->assignee?->name ?? 'Belum ditugaskan' }}</dd></div>
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Estimasi / aktual</dt><dd class="sm:mt-1 tabular">{{ \App\Support\Money::format($maintenance->estimated_cost) }} / {{ \App\Support\Money::format($maintenance->actual_cost) }}</dd></div>
            </dl>
            <p class="mt-4 border-t border-stone pt-4 text-sm dark:border-dark-border">{{ $maintenance->description }}</p>
        </x-ui.card>

        <x-ui.card title="Komentar">
            @foreach ($maintenance->comments as $comment)
                <div class="border-b border-stone py-3 text-sm last:border-0 dark:border-dark-border">
                    <p>{{ $comment->body }}</p>
                    <p class="mt-1 text-xs text-sage dark:text-dark-sage">{{ $comment->user?->name ?? 'Sistem' }} · {{ $comment->created_at->translatedFormat('d M Y H:i') }}</p>
                </div>
            @endforeach
            <form method="POST" action="{{ route('maintenance.comments.store', $maintenance) }}" class="mt-3 space-y-3 border-t border-stone pt-3 dark:border-dark-border">
                @csrf
                <x-ui.textarea label="Tambah komentar" name="body" required />
                <button class="btn-secondary">Kirim komentar</button>
            </form>
        </x-ui.card>
    </div>

    @can('maintenance.manage')
        <x-ui.card title="Ubah status & penugasan">
            <form method="POST" action="{{ route('maintenance.status', $maintenance) }}" class="space-y-3">
                @csrf
                <x-ui.select label="Status" name="status" :selected="$maintenance->status" required
                    :options="collect(\App\Models\MaintenanceRequest::STATUSES)->mapWithKeys(fn ($s) => [$s => ucfirst(str_replace('_', ' ', $s))])->all()" />
                <x-ui.select label="Tugaskan ke" name="assigned_to" :selected="$maintenance->assigned_to"
                    :options="$staff->pluck('name', 'id')->all()" placeholder="Belum ditugaskan" />
                <x-ui.input label="Biaya aktual" name="actual_cost" :value="$maintenance->actual_cost ? number_format($maintenance->actual_cost, 0, ',', '.') : ''" />
                <button class="btn-primary w-full">Simpan</button>
            </form>
        </x-ui.card>
    @endcan
</div>
@endsection