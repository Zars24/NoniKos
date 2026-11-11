@extends('layouts.app')

@section('title', 'Edit booking')

@section('content')
<x-ui.page-header :title="'Edit '.$booking->code" />

<form method="POST" action="{{ route('bookings.update', $booking) }}" class="max-w-xl space-y-4">
    @csrf
    @method('PUT')
    <div class="card space-y-4 p-5">
        <x-ui.input label="Tanggal mulai" name="start_date" type="date" :value="$booking->start_date?->toDateString()" required />
        <x-ui.input label="Durasi (bulan)" name="duration_months" type="number" min="1" max="60" :value="$booking->duration_months" required />
        <x-ui.input label="Harga sewa per bulan" name="monthly_price" :value="number_format($booking->monthly_price, 0, ',', '.')" required />
        <x-ui.input label="Deposit" name="deposit_amount" :value="number_format($booking->deposit_amount, 0, ',', '.')" />
        <x-ui.textarea label="Catatan" name="notes" :value="$booking->notes" />
    </div>
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Simpan</button>
        <a href="{{ route('bookings.index') }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection