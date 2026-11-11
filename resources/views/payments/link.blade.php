<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembayaran {{ $invoice->number }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-ivory">
<div class="mx-auto max-w-lg px-4 py-10">
    <div class="card p-6 text-center">
        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-brand text-lg font-bold text-white dark:bg-dark-champagne dark:text-dark-bg">NK</span>
        <h1 class="mt-4 text-lg font-semibold">{{ $invoice->organization?->name ?? config('app.name') }}</h1>
        <p class="mt-1 text-sm text-sage">Invoice {{ $invoice->number }}</p>

        <p class="mt-6 text-3xl font-semibold tabular">{{ \App\Support\Money::format($link->amount) }}</p>
        <p class="mt-1 text-sm text-sage">Sisa tagihan saat tautan dibuat</p>

        @if ($isSimulation)
            <div class="mt-6 rounded-lg border border-warning/30 bg-warning-soft px-4 py-3 text-left text-sm text-warning">
                <p class="font-semibold">Mode simulasi</p>
                <p class="mt-1">
                    Kredensial payment gateway belum dikonfigurasi. Halaman ini <strong>tidak melakukan pembayaran</strong> dan tidak menandai invoice lunas.
                    Hubungi pengelola untuk pembayaran manual, atau konfigurasikan variabel lingkungan gateway.
                </p>
            </div>
        @else
            <a href="{{ $link->url }}" class="btn-primary mt-6 w-full">Lanjutkan ke pembayaran</a>
            <p class="mt-3 text-xs text-sage">
                Status invoice hanya berubah setelah provider mengirim webhook terverifikasi.
            </p>
        @endif

        <dl class="mt-6 space-y-1 border-t border-stone pt-4 text-left text-sm dark:border-dark-border">
            <div class="flex justify-between"><dt class="text-sage">Penyewa</dt><dd>{{ $invoice->tenant?->full_name }}</dd></div>
            <div class="flex justify-between"><dt class="text-sage">Jatuh tempo</dt><dd>{{ $invoice->due_date->translatedFormat('d F Y') }}</dd></div>
            <div class="flex justify-between"><dt class="text-sage">Status</dt><dd><x-ui.status-badge :status="$invoice->status" /></dd></div>
        </dl>
    </div>
</div>
</body>
</html>