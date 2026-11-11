<x-guest-layout title="Masuk">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-ink dark:text-dark-ink">Masuk ke akun Anda</h1>
        <p class="mt-1 text-sm text-sage dark:text-dark-sage">Gunakan email dan kata sandi yang terdaftar.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <x-ui.input label="Email" name="email" type="email" :value="old('email')" required autofocus autocomplete="username" />

        <div>
            <x-ui.input label="Kata sandi" name="password" type="password" required autocomplete="current-password" />
            @if (Route::has('password.request'))
                <a class="mt-2 inline-block text-xs font-medium text-brand hover:underline dark:text-dark-champagne" href="{{ route('password.request') }}">
                    Lupa kata sandi?
                </a>
            @endif
        </div>

        <label for="remember_me" class="flex items-center gap-2 text-sm text-sage dark:text-dark-sage">
            <input id="remember_me" type="checkbox" name="remember" class="rounded border-stone text-brand focus:ring-champagne dark:border-dark-border dark:bg-dark-elevated">
            Ingat saya di perangkat ini
        </label>

        <button type="submit" class="btn-primary w-full">Masuk</button>

        <p class="text-center text-sm text-sage dark:text-dark-sage">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-medium text-brand hover:underline dark:text-dark-champagne">Daftar organisasi</a>
        </p>
    </form>
</x-guest-layout>
