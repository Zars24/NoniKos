@extends('layouts.app')

@section('title', 'Notifikasi saya')

@section('content')
<x-ui.page-header title="Notifikasi saya" subtitle="Pesan tagihan dan pengingat yang dikirim ke Anda." />

@if ($notifications->isEmpty())
    <x-ui.empty-state title="Belum ada notifikasi" description="Pengingat tagihan akan muncul di sini setelah dikirim." />
@else
    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr><th scope="col">Tanggal</th><th scope="col">Jenis</th><th scope="col">Pesan</th><th scope="col">Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($notifications as $notification)
                        <tr>
                            <td>{{ $notification->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td>{{ $notification->template_key }}</td>
                            <td class="max-w-md whitespace-normal">{{ $notification->body }}</td>
                            <td><x-ui.status-badge :status="$notification->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.pagination :paginator="$notifications" />
@endif
@endsection
