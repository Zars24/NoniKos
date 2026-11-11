@extends('layouts.app')

@section('title', 'Kontrak '.$agreement->code)

@section('content')
<x-ui.page-header :title="'Kontrak '.$agreement->code" :subtitle="$agreement->tenant?->full_name.' · Kamar '.$agreement->room?->number">
    <x-slot:actions>
        <a href="{{ route('agreements.pdf', $agreement) }}" class="btn-secondary">Unduh PDF</a>
        @can('contracts.manage')
            <form method="POST" action="{{ route('agreements.invoice', $agreement) }}" class="inline">
                @csrf
                <button class="btn-secondary">Tagihan periode ini</button>
            </form>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<div class="grid gap-4 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        <x-ui.card title="Ringkasan kontrak">
            <dl class="grid gap-2 text-sm sm:grid-cols-2">
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Status</dt><dd class="sm:mt-1"><x-ui.status-badge :status="$agreement->status" /></dd></div>
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Penyewa</dt><dd class="sm:mt-1">{{ $agreement->tenant?->full_name }}</dd></div>
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Properti</dt><dd class="sm:mt-1">{{ $agreement->room?->property?->name }}</dd></div>
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Kamar</dt><dd class="sm:mt-1">{{ $agreement->room?->number }}</dd></div>
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Mulai</dt><dd class="sm:mt-1">{{ $agreement->start_date->translatedFormat('d F Y') }}</dd></div>
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Berakhir</dt><dd class="sm:mt-1">{{ $agreement->end_date->translatedFormat('d F Y') }}</dd></div>
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Sewa</dt><dd class="sm:mt-1 tabular">{{ \App\Support\Money::format($agreement->monthly_price) }} / bulan</dd></div>
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Deposit</dt><dd class="sm:mt-1 tabular">{{ \App\Support\Money::format($agreement->deposit_amount) }}</dd></div>
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Siklus penagihan</dt><dd class="sm:mt-1">{{ $agreement->billing_cycle_months }} bulan · jatuh tempo {{ $agreement->due_days }} hari</dd></div>
                <div class="flex justify-between sm:block"><dt class="text-sage dark:text-dark-sage">Check-in / out</dt><dd class="sm:mt-1">{{ $agreement->checked_in_at?->translatedFormat('d M Y') ?? '—' }} / {{ $agreement->checked_out_at?->translatedFormat('d M Y') ?? '—' }}</dd></div>
            </dl>
            @if ($agreement->termination_reason)
                <x-ui.alert variant="warning" class="mt-4" title="Dihentikan">Alasan: {{ $agreement->termination_reason }}</x-ui.alert>
            @endif
        </x-ui.card>

        <x-ui.card title="Invoice kontrak ini">
            @forelse ($agreement->invoices as $invoice)
                <div class="flex items-center justify-between gap-3 border-b border-stone py-3 text-sm last:border-0 dark:border-dark-border">
                    <div>
                        <a href="{{ route('invoices.show', $invoice) }}" class="font-medium hover:underline">{{ $invoice->number }}</a>
                        <p class="text-xs text-sage dark:text-dark-sage">{{ $invoice->period_start->translatedFormat('d M Y') }} – {{ $invoice->period_end->translatedFormat('d M Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="tabular">{{ \App\Support\Money::format($invoice->total) }}</p>
                        <x-ui.status-badge :status="$invoice->status" />
                    </div>
                </div>
            @empty
                <p class="text-sm text-sage dark:text-dark-sage">Belum ada invoice.</p>
            @endforelse
        </x-ui.card>
    </div>

    <div class="space-y-4">
        @can('contracts.manage')
            <x-ui.card title="Perpanjang kontrak">
                <form method="POST" action="{{ route('agreements.renew', $agreement) }}" class="space-y-3">
                    @csrf
                    <x-ui.input label="Tanggal akhir baru" name="end_date" type="date" :value="$agreement->end_date->copy()->addYear()->toDateString()" required />
                    <x-ui.input label="Harga sewa baru (opsional)" name="monthly_price" help="Kosongkan bila tidak berubah." />
                    <button class="btn-secondary w-full">Perpanjang</button>
                </form>
            </x-ui.card>

            <x-ui.card title="Pindah kamar">
                <form method="POST" action="{{ route('agreements.transfer', $agreement) }}" class="space-y-3">
                    @csrf
                    <x-ui.select label="Kamar tujuan" name="new_room_id" required placeholder="Pilih kamar"
                        :options="$agreement->room ? \App\Models\Room::where('id', '!=', $agreement->room_id)->where('is_active', true)->orderBy('number')->get()->mapWithKeys(fn ($r) => [$r->id => $r->number])->all() : []" />
                    <x-ui.input label="Alasan" name="reason" />
                    <button class="btn-secondary w-full">Pindahkan</button>
                </form>
            </x-ui.card>

            <x-ui.card title="Check-in / check-out">
                <form method="POST" action="{{ route('agreements.check-in', $agreement) }}" class="mb-2">@csrf<button class="btn-secondary w-full">Catat check-in</button></form>
                <form method="POST" action="{{ route('agreements.check-out', $agreement) }}" onsubmit="return confirm('Catat check-out dan hentikan kontrak ini?')">@csrf<button class="btn-secondary w-full">Catat check-out</button></form>
            </x-ui.card>

            <x-ui.card title="Hentikan kontrak">
                <form method="POST" action="{{ route('agreements.terminate', $agreement) }}" class="space-y-3" onsubmit="return confirm('Hentikan kontrak ini?')">
                    @csrf
                    <x-ui.input label="Alasan penghentian" name="reason" required />
                    <button class="btn-danger w-full">Hentikan kontrak</button>
                </form>
            </x-ui.card>
        @endcan
    </div>
</div>
@endsection