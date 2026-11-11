<x-guest-layout title="Verifikasi email">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-ink dark:text-dark-ink">Verifikasi email Anda</h1>
        <p class="mt-1 text-sm text-sage dark:text-dark-sage">
            Terima kasih sudah mendaftar. Klik tautan verifikasi yang kami kirim ke email Anda.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <x-ui.alert variant="success" class="mb-4">Tautan verifikasi baru telah dikirim ke email Anda.</x-ui.alert>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row">
        <form method="POST" action="{{ route('verification.send') }}" class="flex-1">
            @csrf
            <button type="submit" class="btn-primary w-full">Kirim ulang email verifikasi</button>
        </form>
        <form method="POST" action="{{ route('logout') }}" class="flex-1">
            @csrf
            <button type="submit" class="btn-secondary w-full">Keluar</button>
        </form>
    </div>
</x-guest-layout>
