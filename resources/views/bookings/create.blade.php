@extends('layouts.app')

@section('title', 'Booking baru')

@section('content')
<x-ui.page-header title="Booking baru" />

<form method="POST" action="{{ route('bookings.store') }}" class="max-w-xl space-y-4">
    @csrf
    <div class="card space-y-4 p-5">
        <x-ui.select label="Kamar" name="room_id" required placeholder="Pilih kamar"
            :options="$rooms->mapWithKeys(fn ($r) => [$r->id => $r->number.' — '.$r->property?->name])->all()" />
        <x-ui.select label="Penyewa" name="tenant_id" required placeholder="Pilih penyewa"
            :options="$tenants->pluck('full_name', 'id')->all()" />
        <x-ui.input label="Tanggal mulai" name="start_date" type="date" :value="$booking->start_date?->toDateString()" required />
        <x-ui.input label="Durasi (bulan)" name="duration_months" type="number" min="1" max="60" :value="$booking->duration_months ?? 12" required />
        <x-ui.input label="Harga sewa per bulan" name="monthly_price" required help="Contoh: 1.500.000" />
        <x-ui.input label="Deposit" name="deposit_amount" value="0" />
        <x-ui.textarea label="Catatan" name="notes" />
    </div>
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Simpan booking</button>
        <a href="{{ route('bookings.index') }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection