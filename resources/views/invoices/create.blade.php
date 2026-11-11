@extends('layouts.app')

@section('title', 'Invoice baru')

@section('content')
<x-ui.page-header title="Invoice baru" subtitle="Penagihan manual untuk satu periode kontrak." />

<form method="POST" action="{{ route('invoices.store') }}" class="max-w-xl space-y-4">
    @csrf
    <div class="card space-y-4 p-5">
        <x-ui.select label="Kontrak aktif" name="rental_agreement_id" required placeholder="Pilih kontrak"
            :options="$agreements->mapWithKeys(fn ($a) => [$a->id => $a->code.' — '.$a->tenant?->full_name.' (Kamar '.$a->room?->number.')'])->all()" />
        <x-ui.input label="Periode mulai" name="period_start" type="date" :value="today()->startOfMonth()->toDateString()" required help="Invoice duplikat untuk periode yang sama akan dikembalikan, bukan dibuat ulang." />
    </div>
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Buat invoice</button>
        <a href="{{ route('invoices.index') }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection