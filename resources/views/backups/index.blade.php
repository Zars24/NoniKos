@extends('layouts.app')

@section('title', 'Backup')

@section('content')
<x-ui.page-header title="Backup & restore" subtitle="Backup seluruh database, disimpan di penyimpanan privat dan diverifikasi dengan checksum.">
    <x-slot:actions>
        <form method="POST" action="{{ route('platform.backups.store') }}">
            @csrf
            <button class="btn-primary">Buat backup sekarang</button>
        </form>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.alert variant="warning" class="mb-4" title="Perhatian restore">
    Backup berisi data seluruh organisasi dan hanya dapat diakses administrator platform.
    Restore bersifat destruktif terhadap data saat ini; sistem otomatis membuat backup pengaman sebelum restore.
    Uji restore pada environment terpisah sebelum menggunakannya di produksi.
</x-ui.alert>

@if ($records->isEmpty())
    <x-ui.empty-state title="Belum ada backup" description="Buat backup manual atau jadwalkan melalui scheduler." />
@else
    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr><th scope="col">Berkas</th><th scope="col">Dibuat</th><th scope="col" class="text-right">Ukuran</th><th scope="col">Status</th><th scope="col" class="text-right">Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach ($records as $record)
                        <tr>
                            <td class="font-mono text-xs">{{ $record->filename }}</td>
                            <td>{{ $record->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="tabular text-right">{{ $record->size ? number_format($record->size / 1024, 1).' KB' : '—' }}</td>
                            <td><x-ui.status-badge :status="$record->status" /></td>
                            <td class="text-right">
                                <div class="flex justify-end gap-1">
                                    @if ($record->status === 'completed')
                                        <a href="{{ route('platform.backups.download', $record) }}" class="btn-ghost px-2 py-1">Unduh</a>

                                        <form method="POST" action="{{ route('platform.backups.restore', $record) }}" onsubmit="return confirm('Restore database dari backup ini? Data saat ini akan diganti.')">
                                            @csrf
                                            <input type="hidden" name="confirm" value="RESTORE">
                                            <button class="btn-ghost px-2 py-1 text-danger">Restore</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.pagination :paginator="$records" />
@endif
@endsection
