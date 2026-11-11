<x-guest-layout title="Lupa kata sandi">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-ink dark:text-dark-ink">Lupa kata sandi?</h1>
        <p class="mt-1 text-sm text-sage dark:text-dark-sage">Masukkan email Anda. Kami kirim tautan untuk mengatur ulang kata sandi.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-ui.input label="Email" name="email" type="email" :value="old('email')" required autofocus />
        <button type="submit" class="btn-primary w-full">Kirim tautan reset</button>
        <p class="text-center text-sm">
            <a href="{{ route('login') }}" class="font-medium text-brand hover:underline dark:text-dark-champagne">Kembali ke halaman masuk</a>
        </p>
    </form>
</x-guest-layout>
