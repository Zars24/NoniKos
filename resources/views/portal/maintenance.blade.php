@extends('layouts.app')

@section('title', 'Keluhan saya')

@section('content')
<x-ui.page-header title="Keluhan & pemeliharaan" subtitle="Ajukan keluhan kerusakan dan pantau statusnya." />

<x-ui.card title="Ajukan keluhan baru" class="mb-4">
    <form method="POST" action="{{ route('portal.maintenance.store') }}" class="space-y-3">
        @csrf
        <x-ui.input label="Judul" name="title" required />
        <x-ui.select label="Kategori" name="category" required
            :options="collect(\App\Models\MaintenanceRequest::CATEGORIES)->mapWithKeys(fn ($c) => [$c => ucfirst($c)])->all()" />
        <x-ui.textarea label="Deskripsi" name="description" required />
        <button class="btn-primary">Kirim keluhan</button>
    </form>
</x-ui.card>

@if ($requests->isEmpty())
    <x-ui.empty-state title="Belum ada keluhan" />
@else
    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr><th scope="col">Judul</th><th scope="col">Kategori</th><th scope="col">Tanggal</th><th scope="col">Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        <tr>
                            <td>{{ $request->title }}</td>
                            <td>{{ ucfirst($request->category) }}</td>
                            <td>{{ $request->created_at->translatedFormat('d M Y') }}</td>
                            <td><x-ui.status-badge :status="$request->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>
@endif
@endsection