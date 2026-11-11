@extends('layouts.app')

@section('title', 'Properti')

@section('content')
<x-ui.page-header title="Properti" subtitle="Kelola gedung, kamar, dan performa setiap properti.">
    <x-slot:actions>
        @can('properties.create')
            <a href="{{ route('properties.create') }}" class="btn-primary">+ Properti baru</a>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
    <label for="q" class="sr-only">Cari properti</label>
    <input id="q" name="q" value="{{ request('q') }}" placeholder="Cari nama, kode, atau kota…" class="input sm:max-w-xs">
    <label for="status" class="sr-only">Status</label>
    <select id="status" name="status" class="input sm:max-w-[10rem]">
        <option value="">Semua status</option>
        <option value="active" @selected(request('status') === 'active')>Aktif</option>
        <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
    </select>
    <button class="btn-secondary" type="submit">Filter</button>
</form>

@if ($properties->isEmpty())
    <x-ui.empty-state title="Belum ada properti" description="Tambahkan properti pertama untuk mulai mengelola kamar dan penyewa.">
        <x-slot:action>
            @can('properties.create')
                <a href="{{ route('properties.create') }}" class="btn-primary">Tambah properti</a>
            @endcan
        </x-slot:action>
    </x-ui.empty-state>
@else
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($properties as $property)
            <article class="card flex flex-col p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-semibold text-ink dark:text-dark-ink">{{ $property->name }}</h2>
                        <p class="mt-1 truncate text-sm text-sage dark:text-dark-sage">
                            {{ collect([$property->city, $property->province])->filter()->join(', ') ?: 'Alamat belum diisi' }}
                        </p>
                    </div>
                    <x-ui.badge :variant="$property->is_active ? 'success' : 'muted'" :label="$property->is_active ? 'Aktif' : 'Nonaktif'" />
                </div>

                <dl class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <div class="rounded-lg surface-muted px-2 py-2">
                        <dt class="text-[11px] uppercase tracking-wide text-sage dark:text-dark-sage">Kamar</dt>
                        <dd class="tabular text-lg font-semibold">{{ $property->rooms_count }}</dd>
                    </div>
                    <div class="rounded-lg surface-muted px-2 py-2">
                        <dt class="text-[11px] uppercase tracking-wide text-sage dark:text-dark-sage">Terisi</dt>
                        <dd class="tabular text-lg font-semibold">{{ $property->occupied_rooms_count }}</dd>
                    </div>
                    <div class="rounded-lg surface-muted px-2 py-2">
                        <dt class="text-[11px] uppercase tracking-wide text-sage dark:text-dark-sage">Kode</dt>
                        <dd class="text-sm font-semibold">{{ $property->code ?: '—' }}</dd>
                    </div>
                </dl>

                <div class="mt-4 flex items-center gap-2 border-t border-stone pt-4 dark:border-dark-border">
                    <a href="{{ route('rooms.index', ['property_id' => $property->id]) }}" class="btn-secondary flex-1">Lihat kamar</a>
                    @can('properties.update')
                        <a href="{{ route('properties.edit', $property) }}" class="btn-ghost">Edit</a>
                    @endcan
                </div>
            </article>
        @endforeach
    </div>

    <x-ui.pagination :paginator="$properties" />
@endif
@endsection
