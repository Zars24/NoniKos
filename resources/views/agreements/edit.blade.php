@extends('layouts.app')

@section('title', 'Edit kontrak')

@section('content')
<x-ui.page-header :title="'Edit kontrak '.$agreement->code" subtitle="Harga dan tanggal hanya dapat diubah melalui perpanjangan atau pemindahan kamar." />

<form method="POST" action="{{ route('agreements.update', $agreement) }}" class="max-w-xl space-y-4">
    @csrf
    @method('PUT')
    <div class="card space-y-4 p-5">
        <x-ui.input label="Siklus penagihan (bulan)" name="billing_cycle_months" type="number" min="1" max="24" :value="$agreement->billing_cycle_months" required />
        <x-ui.input label="Jatuh tempo (hari)" name="due_days" type="number" min="0" max="90" :value="$agreement->due_days" required />
        <x-ui.textarea label="Catatan" name="notes" :value="$agreement->notes" />
    </div>
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Simpan</button>
        <a href="{{ route('agreements.show', $agreement) }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection