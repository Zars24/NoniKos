<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #343832; font-size: 12px; margin: 0; }
        .header { border-bottom: 3px solid #203B32; padding-bottom: 12px; margin-bottom: 18px; }
        .brand { color: #203B32; font-size: 22px; font-weight: bold; }
        .muted { color: #879889; }
        .title { font-size: 16px; font-weight: bold; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #F6F2E9; text-align: left; padding: 8px; font-size: 11px; text-transform: uppercase; color: #879889; }
        td { padding: 8px; border-bottom: 1px solid #E5DED1; }
        .right { text-align: right; }
        .totals td { border: none; padding: 4px 8px; }
        .grand { font-weight: bold; font-size: 14px; border-top: 2px solid #203B32; }
        .badge { display: inline-block; border: 1px solid #C4A46B; color: #A98A55; border-radius: 10px; padding: 2px 8px; font-size: 10px; }
        .footer { margin-top: 24px; font-size: 10px; color: #879889; border-top: 1px solid #E5DED1; padding-top: 8px; }
        .box { background: #F6F2E9; padding: 10px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="border:none; padding:0;">
                    <div class="brand">{{ $invoice->organization?->name ?? config('app.name') }}</div>
                    <div class="muted">
                        {{ $invoice->organization?->address }}<br>
                        {{ $invoice->organization?->phone }} {{ $invoice->organization?->email ? '· '.$invoice->organization->email : '' }}
                    </div>
                </td>
                <td style="border:none; padding:0;" class="right">
                    <div class="title">INVOICE</div>
                    <div class="muted">{{ $invoice->number }}</div>
                    <div class="badge">{{ strtoupper(str_replace('_', ' ', $invoice->status)) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table style="margin-bottom: 18px;">
        <tr>
            <td style="border:none; padding:0; vertical-align: top; width: 50%;">
                <div class="muted">Ditagihkan kepada</div>
                <div style="font-weight:bold;">{{ $invoice->tenant?->full_name }}</div>
                <div class="muted">{{ $invoice->tenant?->phone }}<br>{{ $invoice->tenant?->address }}</div>
            </td>
            <td style="border:none; padding:0; vertical-align: top;" class="right">
                <div class="muted">Properti</div>
                <div style="font-weight:bold;">{{ $invoice->property?->name }}</div>
                <div class="muted">Kamar {{ $invoice->agreement?->room?->number }}</div>
                <div class="muted">Jatuh tempo: {{ $invoice->due_date->translatedFormat('d F Y') }}</div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Deskripsi</th>
                <th class="right">Qty</th>
                <th class="right">Harga</th>
                <th class="right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($item->amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="margin-top: 14px; width: 45%; margin-left: auto;">
        <tr><td class="muted">Subtotal</td><td class="right">{{ number_format($invoice->subtotal, 0, ',', '.') }}</td></tr>
        @if ($invoice->discount_total > 0)
            <tr><td class="muted">Diskon</td><td class="right">−{{ number_format($invoice->discount_total, 0, ',', '.') }}</td></tr>
        @endif
        @if ($invoice->late_fee_total > 0)
            <tr><td class="muted">Denda</td><td class="right">{{ number_format($invoice->late_fee_total, 0, ',', '.') }}</td></tr>
        @endif
        <tr class="grand"><td>Total</td><td class="right">Rp {{ number_format($invoice->total, 0, ',', '.') }}</td></tr>
        <tr><td class="muted">Dibayar</td><td class="right">Rp {{ number_format($invoice->amount_paid, 0, ',', '.') }}</td></tr>
        <tr style="font-weight:bold;"><td>Sisa tagihan</td><td class="right">Rp {{ number_format($invoice->balance(), 0, ',', '.') }}</td></tr>
    </table>

    @if ($invoice->notes)
        <div class="box" style="margin-top: 18px;"><strong>Catatan:</strong> {{ $invoice->notes }}</div>
    @endif

    <div class="footer">
        Invoice ini dihasilkan oleh {{ config('app.name') }} pada {{ now()->translatedFormat('d F Y H:i') }}.
        Dokumen sah tanpa tanda tangan basah. Simpan sebagai bukti transaksi.
    </div>
</body>
</html>