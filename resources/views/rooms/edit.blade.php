@extends('layouts.app')

@section('title', 'Kelola kamar')

@section('content')
<x-ui.page-header :title="'Kamar '.$room->number" :subtitle="$room->property?->name" />

<div class="grid gap-4 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        <form method="POST" action="{{ route('rooms.update', $room) }}" class="space-y-4">
            @csrf
            @method('PUT')
            @include('rooms._form', ['room' => $room])
            <div class="flex gap-2">
                <button type="submit" class="btn-primary">Simpan perubahan</button>
                <a href="{{ route('rooms.index') }}" class="btn-secondary">Kembali</a>
            </div>
        </form>

        <x-ui.card title="Foto kamar">
            <div class="grid gap-3 sm:grid-cols-3">
                @forelse ($photos as $photo)
                    <figure class="overflow-hidden rounded-lg border border-stone dark:border-dark-border">
                        <img src="{{ route('rooms.photo', $room) }}" alt="Foto kamar {{ $room->number }}" class="h-28 w-full object-cover" loading="lazy">
                        <figcaption class="flex items-center justify-between gap-2 p-2 text-xs">
                            <span class="truncate">{{ $photo->caption ?: 'Foto kamar' }}</span>
                            <x-ui.delete-form :action="route('rooms.photos.destroy', [$room, $photo])" method="DELETE" label="Hapus" confirm="Hapus foto ini?" />
                        </figcaption>
                    </figure>
                @empty
                    <p class="text-sm text-sage dark:text-dark-sage">Belum ada foto.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('rooms.photos.store', $room) }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-2 border-t border-stone pt-4 dark:border-dark-border sm:flex-row sm:items-end">
                @csrf
                <div class="flex-1">
                    <label for="photo" class="label">Unggah foto</label>
                    <input id="photo" type="file" name="photo" accept="image/png,image/jpeg,image/webp" required class="input mt-1 file:mr-3 file:rounded file:border-0 file:bg-brand file:px-3 file:py-1.5 file:text-white">
                    @error('photo')<p class="mt-1 text-xs text-danger">{{ $message }}</p>@enderror
                </div>
                <div class="flex-1">
                    <x-ui.input label="Keterangan" name="caption" />
                </div>
                <button type="submit" class="btn-secondary">Unggah</button>
            </form>
        </x-ui.card>
    </div>

    <div class="space-y-4">
        <x-ui.card title="Status operasional">
            <p class="mb-3 text-sm text-sage dark:text-dark-sage">
                Status <strong>Terisi</strong> hanya bisa berasal dari kontrak aktif.
            </p>
            <form method="POST" action="{{ route('rooms.status', $room) }}" class="space-y-3">
                @csrf
                <x-ui.select label="Status" name="status" :selected="$room->status" :options="collect(\App\Models\Room::STATUSES)->mapWithKeys(fn ($s) => [$s => ucfirst($s)])->all()" />
                <x-ui.input label="Alasan" name="reason" />
                <button type="submit" class="btn-secondary w-full">Perbarui status</button>
            </form>
        </x-ui.card>

        <x-ui.card title="Riwayat status">
            @forelse ($histories as $history)
                <div class="border-b border-stone py-2 text-sm last:border-0 dark:border-dark-border">
                    <p class="font-medium">{{ ucfirst($history->to_status) }}</p>
                    <p class="text-xs text-sage dark:text-dark-sage">
                        {{ $history->created_at->translatedFormat('d M Y H:i') }}
                        @if ($history->reason) · {{ $history->reason }} @endif
                    </p>
                </div>
            @empty
                <p class="text-sm text-sage dark:text-dark-sage">Belum ada riwayat.</p>
            @endforelse
        </x-ui.card>

        @can('rooms.manage')
            <x-ui.card title="Hapus kamar">
                <x-ui.delete-form :action="route('rooms.destroy', $room)" method="DELETE" label="Hapus kamar" confirm="Hapus kamar ini?" />
            </x-ui.card>
        @endcan
    </div>
</div>
@endsection
