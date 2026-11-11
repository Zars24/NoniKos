@extends('layouts.app')

@section('title', $tenant->full_name)

@section('content')
<x-ui.page-header :title="$tenant->full_name" :subtitle="'Kode '.$tenant->code">
    <x-slot:actions>
        @can('tenants.manage')
            <a href="{{ route('tenants.edit', $tenant) }}" class="btn-secondary">Edit</a>
        @endcan
        @can('contracts.manage')
            <a href="{{ route('agreements.create', ['tenant_id' => $tenant->id]) }}" class="btn-primary">Buat kontrak</a>
        @endcan
    </x-slot:actions>
</x-ui.page-header>

<div class="grid gap-4 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        <x-ui.card title="Riwayat kontrak">
            @forelse ($tenant->agreements as $agreement)
                <div class="flex items-start justify-between gap-3 border-b border-stone py-3 last:border-0 dark:border-dark-border">
                    <div>
                        <p class="text-sm font-medium">Kamar {{ $agreement->room?->number }} · {{ $agreement->code }}</p>
                        <p class="text-xs text-sage dark:text-dark-sage">
                            {{ $agreement->start_date->translatedFormat('d M Y') }} – {{ $agreement->end_date->translatedFormat('d M Y') }}
                            · {{ \App\Support\Money::format($agreement->monthly_price) }}/bulan
                        </p>
                    </div>
                    <x-ui.status-badge :status="$agreement->status" />
                </div>
            @empty
                <p class="text-sm text-sage dark:text-dark-sage">Belum ada kontrak.</p>
            @endforelse
        </x-ui.card>

        <x-ui.card title="Invoice terakhir">
            @forelse ($tenant->invoices as $invoice)
                <div class="flex items-center justify-between gap-3 border-b border-stone py-3 text-sm last:border-0 dark:border-dark-border">
                    <div>
                        <a href="{{ route('invoices.show', $invoice) }}" class="font-medium hover:underline">{{ $invoice->number }}</a>
                        <p class="text-xs text-sage dark:text-dark-sage">{{ $invoice->period_start->translatedFormat('d M Y') }} · jatuh tempo {{ $invoice->due_date->translatedFormat('d M Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="tabular font-medium">{{ \App\Support\Money::format($invoice->total) }}</p>
                        <x-ui.status-badge :status="$invoice->status" />
                    </div>
                </div>
            @empty
                <p class="text-sm text-sage dark:text-dark-sage">Belum ada invoice.</p>
            @endforelse
        </x-ui.card>

        <x-ui.card title="Pembayaran terakhir">
            @forelse ($tenant->payments as $payment)
                <div class="flex items-center justify-between gap-3 border-b border-stone py-3 text-sm last:border-0 dark:border-dark-border">
                    <div>
                        <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</p>
                        <p class="text-xs text-sage dark:text-dark-sage">{{ $payment->paid_at->translatedFormat('d M Y') }} · {{ $payment->reference ?: 'tanpa referensi' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="tabular font-medium">{{ \App\Support\Money::format($payment->amount) }}</p>
                        <x-ui.status-badge :status="$payment->status" />
                    </div>
                </div>
            @empty
                <p class="text-sm text-sage dark:text-dark-sage">Belum ada pembayaran.</p>
            @endforelse
        </x-ui.card>
    </div>

    <div class="space-y-4">
        <x-ui.card title="Profil">
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-sage dark:text-dark-sage">Status</dt><dd><x-ui.status-badge :status="$tenant->status" /></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-sage dark:text-dark-sage">Telepon</dt><dd>{{ $tenant->phone ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-sage dark:text-dark-sage">Email</dt><dd class="truncate">{{ $tenant->email ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-sage dark:text-dark-sage">Kontak darurat</dt><dd>{{ $tenant->emergency_name ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-sage dark:text-dark-sage">Telepon darurat</dt><dd>{{ $tenant->emergency_phone ?: '—' }}</dd></div>
            </dl>
            @if ($tenant->address)
                <p class="mt-3 border-t border-stone pt-3 text-sm text-sage dark:border-dark-border dark:text-dark-sage">{{ $tenant->address }}</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Dokumen">
            <ul class="space-y-2 text-sm">
                @forelse ($tenant->documents as $document)
                    <li class="flex items-center justify-between gap-2">
                        <a href="{{ route('tenants.documents.download', [$tenant, $document]) }}" class="truncate hover:underline">{{ $document->label }}</a>
                        <span class="text-xs text-sage">{{ number_format(($document->size ?? 0) / 1024, 0) }} KB</span>
                    </li>
                @empty
                    <li class="text-sage dark:text-dark-sage">Belum ada dokumen.</li>
                @endforelse
            </ul>

            @can('tenants.manage')
                <form method="POST" action="{{ route('tenants.documents.store', $tenant) }}" enctype="multipart/form-data" class="mt-3 space-y-2 border-t border-stone pt-3 dark:border-dark-border">
                    @csrf
                    <x-ui.input label="Label dokumen" name="label" required />
                    <input type="file" name="document" accept=".pdf,image/png,image/jpeg" required class="input file:mr-3 file:rounded file:border-0 file:bg-brand file:px-3 file:py-1.5 file:text-white">
                    @error('document')<p class="text-xs text-danger">{{ $message }}</p>@enderror
                    <button type="submit" class="btn-secondary w-full">Unggah dokumen</button>
                </form>
            @endcan
        </x-ui.card>

        @can('tenants.manage')
            <x-ui.card title="Hapus penyewa">
                <p class="text-sm text-sage dark:text-dark-sage">Penyewa dengan riwayat keuangan hanya akan dinonaktifkan.</p>
                <div class="mt-3">
                    <x-ui.delete-form :action="route('tenants.destroy', $tenant)" method="DELETE" label="Hapus penyewa" confirm="Hapus penyewa ini?" />
                </div>
            </x-ui.card>
        @endcan
    </div>
</div>
@endsection
