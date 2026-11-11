<x-guest-layout title="Daftar">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-ink dark:text-dark-ink">Buat akun pemilik</h1>
        <p class="mt-1 text-sm text-sage dark:text-dark-sage">Akun pertama otomatis menjadi Owner organisasi Anda.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <x-ui.input label="Nama lengkap" name="name" :value="old('name')" required autofocus autocomplete="name" />
        <x-ui.input label="Nama organisasi / bisnis kos" name="organization_name" :value="old('organization_name')" required help="Contoh: Kos Melati Jaya" />
        <x-ui.input label="Email" name="email" type="email" :value="old('email')" required autocomplete="username" />
        <x-ui.input label="Kata sandi" name="password" type="password" required autocomplete="new-password" help="Minimal 8 karakter." />
        <x-ui.input label="Ulangi kata sandi" name="password_confirmation" type="password" required autocomplete="new-password" />

        <button type="submit" class="btn-primary w-full">Daftar</button>

        <p class="text-center text-sm text-sage dark:text-dark-sage">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-medium text-brand hover:underline dark:text-dark-champagne">Masuk</a>
        </p>
    </form>
</x-guest-layout>
