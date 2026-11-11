<x-guest-layout title="Konfirmasi kata sandi">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-ink dark:text-dark-ink">Konfirmasi kata sandi</h1>
        <p class="mt-1 text-sm text-sage dark:text-dark-sage">Area ini memerlukan konfirmasi kata sandi sebelum melanjutkan.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf
        <x-ui.input label="Kata sandi" name="password" type="password" required autofocus autocomplete="current-password" />
        <button type="submit" class="btn-primary w-full">Konfirmasi</button>
    </form>
</x-guest-layout>
