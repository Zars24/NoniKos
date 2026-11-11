@extends('layouts.app')

@section('title', 'Profil')

@section('content')
<x-ui.page-header title="Profil & pengaturan akun" subtitle="Perbarui data diri, kata sandi, dan preferensi tampilan." />

<div class="grid gap-4 lg:grid-cols-2">
    <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        @method('PATCH')
        <x-ui.card title="Informasi profil">
            <div class="space-y-4">
                <x-ui.input label="Nama" name="name" :value="$user->name" required />
                <x-ui.input label="Email" name="email" type="email" :value="$user->email" required />
                <x-ui.input label="Telepon" name="phone" :value="$user->phone" />
                <x-ui.select label="Preferensi tema" name="theme_preference" :selected="$user->preferredTheme()"
                    :options="['system' => 'Ikuti sistem', 'light' => 'Terang', 'dark' => 'Gelap']" />
            </div>
            <button class="btn-primary mt-4">Simpan profil</button>
        </x-ui.card>
    </form>

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('PUT')
        <x-ui.card title="Ubah kata sandi">
            <div class="space-y-4">
                <x-ui.input label="Kata sandi saat ini" name="current_password" type="password" required autocomplete="current-password" />
                <x-ui.input label="Kata sandi baru" name="password" type="password" required autocomplete="new-password" />
                <x-ui.input label="Ulangi kata sandi baru" name="password_confirmation" type="password" required autocomplete="new-password" />
            </div>
            <button class="btn-primary mt-4">Perbarui kata sandi</button>
        </x-ui.card>
    </form>
</div>

<div class="mt-4 grid gap-4 lg:grid-cols-2">
    <x-ui.card title="Keanggotaan organisasi">
        @foreach ($user->activeMemberships()->with('organization')->get() as $membership)
            <div class="flex items-center justify-between gap-3 border-b border-stone py-3 text-sm last:border-0 dark:border-dark-border">
                <span>{{ $membership->organization->name }}</span>
                <x-ui.badge variant="champagne" :label="ucfirst($membership->role)" />
            </div>
        @endforeach
    </x-ui.card>

    @unless ($user->activeMemberships()->where('role', 'owner')->exists())
        <x-ui.card title="Hapus akun">
            <p class="text-sm text-sage dark:text-dark-sage">Akun yang terkait data keuangan tidak dapat dihapus; gunakan deaktivasi oleh owner.</p>
            <form method="POST" action="{{ route('profile.destroy') }}" class="mt-3 space-y-3" onsubmit="return confirm('Hapus akun ini secara permanen?')">
                @csrf
                @method('DELETE')
                <x-ui.input label="Konfirmasi kata sandi" name="password" type="password" required />
                <button class="btn-danger w-full">Hapus akun</button>
            </form>
        </x-ui.card>
    @endunless
</div>
@endsection