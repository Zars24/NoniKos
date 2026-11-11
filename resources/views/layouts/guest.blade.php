<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk' }} — {{ config('app.name') }}</title>
    <script>
        (function () {
            var preference = localStorage.getItem('nonikos-theme') || 'system';
            var dark = preference === 'dark' || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (dark) document.documentElement.classList.add('dark');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full font-sans">
<div class="grid min-h-screen lg:grid-cols-2">
    <div class="relative hidden flex-col justify-between bg-brand p-10 text-white lg:flex dark:bg-[#101815]">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-champagne text-sm font-bold text-brand-deep">NK</span>
            <div>
                <p class="font-semibold tracking-wide">NoniKos</p>
                <p class="text-xs text-white/60">Premium Boarding House Management</p>
            </div>
        </div>

        <div class="max-w-md">
            <p class="text-xs uppercase tracking-[0.3em] text-champagne">Heritage Green & Champagne</p>
            <h1 class="mt-4 font-serif text-4xl leading-tight">Setup sekali, kelola kos lebih mudah setiap hari.</h1>
            <p class="mt-4 text-sm leading-relaxed text-white/70">
                Kelola kamar, penyewa, kontrak, tagihan, pembayaran, dan laporan keuangan dari satu sistem yang tenang dan terpercaya.
            </p>
            <ul class="mt-8 space-y-3 text-sm text-white/80">
                <li class="flex items-center gap-3"><span class="text-champagne" aria-hidden="true">✓</span> Tagihan otomatis dan pencatatan pembayaran yang konsisten</li>
                <li class="flex items-center gap-3"><span class="text-champagne" aria-hidden="true">✓</span> Portal penyewa dan pengingat WhatsApp</li>
                <li class="flex items-center gap-3"><span class="text-champagne" aria-hidden="true">✓</span> Laporan keuangan yang dapat ditelusuri</li>
            </ul>
        </div>

        <p class="text-xs text-white/40">© {{ now()->year }} NoniKos. Semua data tersimpan dalam lingkungan aman.</p>
    </div>

    <div class="flex items-center justify-center px-4 py-10 sm:px-8">
        <div class="w-full max-w-md">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand text-sm font-bold text-white dark:bg-dark-champagne dark:text-dark-bg">NK</span>
                <p class="font-semibold text-ink dark:text-dark-ink">NoniKos</p>
            </div>

            <div class="card p-6 sm:p-8">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
</body>
</html>
