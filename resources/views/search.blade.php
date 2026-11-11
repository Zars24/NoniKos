@extends('layouts.app')

@section('title', 'Hasil pencarian')

@section('content')
@php($isTenant = auth()->user()->isTenant())

<x-ui.page-header :title="'Hasil untuk “'.$term.'”'"
    :subtitle="$isTenant ? 'Pencarian invoice milik Anda.' : 'Pencarian penyewa, kamar, dan invoice di organisasi ini.'" />

@if ($term === '')
    <x-ui.empty-state title="Masukkan kata kunci" description="Cari nama penyewa, nomor kamar, atau nomor invoice." />
@else
    <div class="space-y-4">
        @unless ($isTenant)
            <x-ui.card title="Penyewa">
                @forelse ($tenants as $tenant)
                    <a href="{{ route('tenants.show', $tenant) }}" class="block border-b border-stone py-2 text-sm last:border-0 hover:underline dark:border-dark-border">
                        {{ $tenant->full_name }} <span class="text-xs text-sage">· {{ $tenant->phone }}</span>
                    </a>
                @empty
                    <p class="text-sm text-sage dark:text-dark-sage">Tidak ada penyewa cocok.</p>
                @endforelse
            </x-ui.card>

            <x-ui.card title="Kamar">
                @forelse ($rooms as $room)
                    <a href="{{ route('rooms.edit', $room) }}" class="block border-b border-stone py-2 text-sm last:border-0 hover:underline dark:border-dark-border">
                        Kamar {{ $room->number }} <span class="text-xs text-sage">· {{ $room->property?->name }}</span>
                    </a>
                @empty
                    <p class="text-sm text-sage dark:text-dark-sage">Tidak ada kamar cocok.</p>
                @endforelse
            </x-ui.card>
        @endunless

        <x-ui.card title="Invoice">
            @forelse ($invoices as $invoice)
                <a href="{{ $isTenant ? route('portal.invoices.show', $invoice) : route('invoices.show', $invoice) }}" class="block border-b border-stone py-2 text-sm last:border-0 hover:underline dark:border-dark-border">
                    {{ $invoice->number }} <span class="text-xs text-sage">· {{ $invoice->tenant?->full_name }}</span>
                </a>
            @empty
                <p class="text-sm text-sage dark:text-dark-sage">Tidak ada invoice cocok.</p>
            @endforelse
        </x-ui.card>
    </div>
@endif
@endsection