<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #343832; font-size: 12px; margin: 0; }
        .brand { color: #203B32; font-size: 20px; font-weight: bold; }
        .muted { color: #879889; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        td, th { padding: 6px 8px; border-bottom: 1px solid #E5DED1; vertical-align: top; }
        th { background: #F6F2E9; text-align: left; font-size: 11px; color: #879889; }
        .section { margin-top: 20px; }
        .title { font-size: 15px; font-weight: bold; text-align: center; margin: 18px 0; }
        .sign { margin-top: 40px; }
        .footer { margin-top: 20px; font-size: 10px; color: #879889; }
    </style>
</head>
<body>
    <div class="brand">{{ $agreement->organization?->name ?? config('app.name') }}</div>
    <div class="muted">{{ $agreement->organization?->address }} · {{ $agreement->organization?->phone }}</div>

    <div class="title">SURAT PERJANJIAN SEWA KAMAR</div>
    <p class="muted" style="text-align:center;">Nomor: {{ $agreement->code }}</p>

    <p>Pada hari ini, para pihak sepakat mengadakan perjanjian sewa kamar dengan ketentuan sebagai berikut:</p>

    <table>
        <tr><th style="width: 35%;">Penyewa</th><td>{{ $agreement->tenant?->full_name }} ({{ $agreement->tenant?->phone }})</td></tr>
        <tr><th>Properti</th><td>{{ $agreement->room?->property?->name }} — Kamar {{ $agreement->room?->number }}</td></tr>
        <tr><th>Tanggal mulai</th><td>{{ $agreement->start_date->translatedFormat('d F Y') }}</td></tr>
        <tr><th>Tanggal berakhir</th><td>{{ $agreement->end_date->translatedFormat('d F Y') }}</td></tr>
        <tr><th>Harga sewa</th><td>Rp {{ number_format($agreement->monthly_price, 0, ',', '.') }} per bulan</td></tr>
        <tr><th>Siklus penagihan</th><td>Setiap {{ $agreement->billing_cycle_months }} bulan, jatuh tempo {{ $agreement->due_days }} hari setelah periode dimulai</td></tr>
        <tr><th>Deposit</th><td>Rp {{ number_format($agreement->deposit_amount, 0, ',', '.') }}</td></tr>
        <tr><th>Fasilitas</th><td>{{ $agreement->room?->facilities ?: 'Sesuai kondisi kamar' }}</td></tr>
    </table>

    <div class="section">
        <strong>Ketentuan</strong>
        <ol>
            <li>Penyewa membayar sewa sesuai nominal dan jadwal yang disepakati.</li>
            <li>Keterlambatan pembayaran dapat dikenakan denda sesuai kebijakan pengelola.</li>
            <li>Deposit dikembalikan setelah penyewa keluar, dipotong kewajiban yang belum diselesaikan.</li>
            <li>Perubahan atau pengakhiran dilakukan sesuai kesepakatan kedua pihak.</li>
        </ol>
    </div>

    @if ($agreement->notes)
        <div class="section"><strong>Catatan tambahan:</strong> {{ $agreement->notes }}</div>
    @endif

    <table class="sign">
        <tr>
            <td style="border:none; text-align:center;">Penyewa<br><br><br><br>{{ $agreement->tenant?->full_name }}</td>
            <td style="border:none; text-align:center;">Pengelola<br><br><br><br>{{ $agreement->organization?->name }}</td>
        </tr>
    </table>

    <div class="footer">Dicetak dari {{ config('app.name') }} pada {{ now()->translatedFormat('d F Y H:i') }}.</div>
</body>
</html>