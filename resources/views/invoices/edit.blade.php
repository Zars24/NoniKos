@extends('layouts.app')

@section('title', 'Sunting draf invoice')

@section('content')
<x-ui.page-header :title="'Sunting '.$invoice->number" subtitle="Hanya invoice draf yang dapat disunting." />

<form method="POST" action="{{ route('invoices.update', $invoice) }}" class="max-w-xl space-y-4">
    @csrf
    @method('PUT')
    <div class="card space-y-4 p-5">
        <p class="text-sm text-sage dark:text-dark-sage">
            Subtotal saat ini: <span class="tabular font-medium text-ink dark:text-dark-ink">{{ \App\Support\Money::format($invoice->subtotal) }}</span>
        </p>
        <x-ui.input label="Diskon" name="discount_total" :value="$invoice->discount_total ? number_format($invoice->discount_total, 0, ',', '.') : ''" help="Kosongkan bila tidak ada diskon." />
        <x-ui.input label="Jatuh tempo" name="due_date" type="date" :value="$invoice->due_date->toDateString()" required />
        <x-ui.textarea label="Catatan" name="notes" :value="$invoice->notes" />
    </div>
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Simpan</button>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection