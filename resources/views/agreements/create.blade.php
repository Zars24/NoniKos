@extends('layouts.app')

@section('title', 'Kontrak baru')

@section('content')
<x-ui.page-header title="Kontrak baru" subtitle="Harga dan ketentuan disimpan sebagai snapshot kontrak ini." />

<form method="POST" action="{{ route('agreements.store') }}" class="max-w-xl space-y-4">
    @csrf
    <div class="card space-y-4 p-5">
        <x-ui.select label="Kamar" name="room_id" required placeholder="Pilih kamar"
            :options="$rooms->mapWithKeys(fn ($r) => [$r->id => $r->number.' — '.$r->property?->name.' ('.number_format($r->monthly_price, 0, ',', '.').')'])->all()" />
        <x-ui.select label="Penyewa" name="tenant_id" required placeholder="Pilih penyewa"
            :selected="$selectedTenant" :options="$tenants->pluck('full_name', 'id')->all()" />
        <x-ui.input label="Tanggal mulai" name="start_date" type="date" :value="$agreement->start_date?->toDateString()" required />
        <x-ui.input label="Durasi (bulan)" name="duration_months" type="number" min="1" max="60" value="12" required />
        <x-ui.input label="Harga sewa per bulan" name="monthly_price" required help="Contoh: 1.500.000" />
        <x-ui.input label="Deposit" name="deposit_amount" value="0" />
        <x-ui.input label="Siklus penagihan (bulan)" name="billing_cycle_months" type="number" min="1" max="24" :value="$agreement->billing_cycle_months ?? 1" required />
        <x-ui.input label="Jatuh tempo (hari)" name="due_days" type="number" min="0" max="90" :value="$agreement->due_days ?? 7" required />
        <x-ui.textarea label="Catatan" name="notes" />
    </div>
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Buat kontrak</button>
        <a href="{{ route('agreements.index') }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection