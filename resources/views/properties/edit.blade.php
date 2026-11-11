@extends('layouts.app')

@section('title', 'Edit properti')

@section('content')
<x-ui.page-header :title="'Edit '.$property->name" subtitle="Perubahan berlaku untuk kamar dan tagihan baru." />

<div class="grid max-w-5xl gap-4 lg:grid-cols-3">
    <form method="POST" action="{{ route('properties.update', $property) }}" class="space-y-4 lg:col-span-2">
        @csrf
        @method('PUT')
        @include('properties._form', ['property' => $property])
        <div class="flex gap-2">
            <button type="submit" class="btn-primary">Simpan perubahan</button>
            <a href="{{ route('properties.index') }}" class="btn-secondary">Batal</a>
        </div>
    </form>

    <div class="space-y-4">
        <x-ui.card title="Gedung" description="Opsional, untuk properti dengan beberapa gedung.">
            @if ($buildings->isEmpty())
                <p class="text-sm text-sage dark:text-dark-sage">Belum ada gedung.</p>
            @else
                <ul class="divide-y divide-stone text-sm dark:divide-dark-border">
                    @foreach ($buildings as $building)
                        <li class="flex items-center justify-between gap-2 py-2">
                            <span>{{ $building->name }} <span class="text-xs text-sage">({{ $building->floors }} lantai)</span></span>
                            <x-ui.delete-form :action="route('properties.buildings.destroy', [$property, $building])" method="DELETE" label="Hapus" confirm="Hapus gedung ini?" />
                        </li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('properties.buildings.store', $property) }}" class="mt-4 space-y-3 border-t border-stone pt-4 dark:border-dark-border">
                @csrf
                <x-ui.input label="Nama gedung" name="name" required />
                <x-ui.input label="Jumlah lantai" name="floors" type="number" min="1" max="100" value="1" />
                <button type="submit" class="btn-secondary w-full">Tambah gedung</button>
            </form>
        </x-ui.card>

        @can('properties.delete')
            <x-ui.card title="Zona berbahaya">
                <p class="text-sm text-sage dark:text-dark-sage">
                    Properti dengan riwayat kamar atau kontrak akan dinonaktifkan, bukan dihapus.
                </p>
                <div class="mt-3">
                    <x-ui.delete-form :action="route('properties.destroy', $property)" method="DELETE" label="Hapus properti" confirm="Hapus properti ini? Tindakan ini tidak dapat dibatalkan bila tidak ada riwayat." />
                </div>
            </x-ui.card>
        @endcan
    </div>
</div>
@endsection
