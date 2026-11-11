<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #343832; font-size: 12px; margin: 0; }
        .brand { color: #203B32; font-size: 18px; font-weight: bold; }
        .muted { color: #879889; }
        .title { text-align: center; font-size: 15px; font-weight: bold; margin: 16px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        td { padding: 6px 8px; border-bottom: 1px solid #E5DED1; vertical-align: top; }
        .grand { font-weight: bold; font-size: 14px; }
        .footer { margin-top: 24px; font-size: 10px; color: #879889; }
        .sign { margin-top: 36px; text-align: right; }
    </style>
</head>
<body>
    <div class="brand">{{ $payment->invoice?->organization?->name ?? config('app.name') }}</div>
    <div class="muted">{{ $payment->invoice?->organization?->address }}</div>

    <div class="title">KUITANSI PEMBAYARAN</div>
    <div class="muted" style="text-align:center;">No. {{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</div>

    <table>
        <tr><td class="muted" style="width: 40%;">Telah diterima dari</td><td>{{ $payment->tenant?->full_name }}</td></tr>
        <tr><td class="muted">Untuk invoice</td><td>{{ $payment->invoice?->number }}</td></tr>
        <tr><td class="muted">Properti / kamar</td><td>{{ $payment->invoice?->property?->name }} — Kamar {{ $payment->invoice?->agreement?->room?->number }}</td></tr>
        <tr><td class="muted">Metode</td><td>{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td></tr>
        <tr><td class="muted">Tanggal</td><td>{{ $payment->paid_at->translatedFormat('d F Y') }}</td></tr>
        <tr><td class="muted">Status</td><td>{{ strtoupper($payment->status) }}</td></tr>
        <tr><td class="muted grand">Jumlah</td><td class="grand">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td></tr>
    </table>

    <div class="sign">
        {{ now()->translatedFormat('d F Y') }}<br><br><br>
        {{ $payment->recordedBy?->name ?? 'Pengelola' }}
    </div>

    <div class="footer">Kuitansi ini dihasilkan oleh {{ config('app.name') }}. Simpan sebagai bukti pembayaran yang sah.</div>
</body>
</html>