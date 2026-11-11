@extends('layouts.app')

@section('title', 'Pengaturan organisasi')

@section('content')
<x-ui.page-header title="Pengaturan" subtitle="Profil organisasi, penagihan, dan template pesan." />

<div class="grid gap-4 lg:grid-cols-2">
    <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
        @csrf
        @method('PUT')
        <x-ui.card title="Profil organisasi">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-ui.input label="Nama organisasi" name="name" :value="$organization->name" required />
                </div>
                <x-ui.input label="Email" name="email" type="email" :value="$organization->email" />
                <x-ui.input label="Telepon" name="phone" :value="$organization->phone" />
                <div class="sm:col-span-2">
                    <x-ui.textarea label="Alamat" name="address" :value="$organization->address" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Penagihan">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.input label="Prefiks invoice" name="invoice_prefix" :value="$organization->invoice_prefix" required help="Contoh: INV" />
                <x-ui.input label="Jatuh tempo default (hari)" name="due_days" type="number" min="0" max="90" :value="$organization->due_days" required />
                <x-ui.input label="Denda keterlambatan (%)" name="late_fee_percent" type="number" min="0" max="50" :value="$organization->late_fee_percent" required help="0 = tanpa denda." />
                <x-ui.select label="Zona waktu" name="timezone" :selected="$organization->timezone"
                    :options="['Asia/Jakarta' => 'Asia/Jakarta (WIB)', 'Asia/Makassar' => 'Asia/Makassar (WITA)', 'Asia/Jayapura' => 'Asia/Jayapura (WIT)']" required />
            </div>
            @can('settings.manage')
                <button class="btn-primary mt-4">Simpan pengaturan</button>
            @endcan
        </x-ui.card>
    </form>

    <div class="space-y-4">
        <x-ui.card title="Status integrasi">
            <ul class="space-y-3 text-sm">
                <li class="flex items-center justify-between gap-2">
                    <span>Payment gateway</span>
                    <x-ui.badge :variant="$gatewayLive ? 'success' : 'warning'" :label="$gatewayLive ? 'Live' : 'Mode simulasi'" />
                </li>
                <li class="flex items-center justify-between gap-2">
                    <span>WhatsApp</span>
                    <x-ui.badge :variant="$whatsappLive ? 'success' : 'warning'" :label="$whatsappLive ? 'Live' : 'Mode simulasi'" />
                </li>
            </ul>
            <p class="hint mt-3">
                Kredensial dibaca dari variabel lingkungan (<code>PAYMENT_GATEWAY_*</code>, <code>WHATSAPP_*</code>).
                Selama belum diisi, integrasi berjalan sebagai simulasi dan tidak pernah mengklaim kirim/bayar berhasil.
            </p>
        </x-ui.card>

        @foreach ($templates as $template)
            <x-ui.card :title="'Template: '.$template->name" :description="$template->key">
                <form method="POST" action="{{ route('settings.templates.update', $template) }}" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <x-ui.input label="Nama" name="name" :value="$template->name" required />
                    <x-ui.textarea label="Isi pesan" name="body" :value="$template->body" :rows="4" required />
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked($template->is_active) class="rounded border-stone text-brand focus:ring-champagne dark:border-dark-border dark:bg-dark-elevated">
                        Aktif
                    </label>
                    <button class="btn-secondary w-full">Simpan template</button>
                </form>
            </x-ui.card>
        @endforeach
    </div>
</div>
@endsection