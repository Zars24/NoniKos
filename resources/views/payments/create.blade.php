@extends('layouts.app')

@section('title', 'Catat pembayaran')

@section('content')
<x-ui.page-header title="Catat pembayaran" subtitle="Nominal tidak boleh melebihi sisa tagihan." />

<form method="POST" action="{{ route('payments.store') }}" class="max-w-xl space-y-4">
    @csrf
    <div class="card space-y-4 p-5">
        <x-ui.select label="Invoice" name="invoice_id" required placeholder="Pilih invoice"
            :selected="$selectedInvoice"
            :options="$invoices->mapWithKeys(fn ($i) => [$i->id => $i->number.' — '.$i->tenant?->full_name.' (sisa '.number_format($i->balance(), 0, ',', '.').')'])->all()" />
        <x-ui.input label="Nominal" name="amount" required help="Contoh: 1.500.000" />
        <x-ui.select label="Metode" name="method" required
            :options="collect(\App\Models\Payment::METHODS)->mapWithKeys(fn ($m) => [$m => ucfirst(str_replace('_', ' ', $m))])->all()" />
        <x-ui.input label="Tanggal bayar" name="paid_at" type="date" :value="today()->toDateString()" required />
        <x-ui.input label="Referensi transaksi" name="reference" help="Nomor transfer atau referensi bank (opsional)." />
        <x-ui.textarea label="Catatan" name="notes" />
    </div>
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Simpan pembayaran</button>
        <a href="{{ route('payments.index') }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection