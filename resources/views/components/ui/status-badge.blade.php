@props(['status'])

@php
    $map = [
        'available' => ['success', 'Tersedia'],
        'occupied' => ['brand', 'Terisi'],
        'reserved' => ['champagne', 'Dipesan'],
        'maintenance' => ['warning', 'Perawatan'],
        'unavailable' => ['muted', 'Tidak tersedia'],
        'draft' => ['muted', 'Draf'],
        'issued' => ['info', 'Diterbitkan'],
        'partially_paid' => ['warning', 'Dibayar sebagian'],
        'paid' => ['success', 'Lunas'],
        'overdue' => ['danger', 'Jatuh tempo'],
        'cancelled' => ['muted', 'Dibatalkan'],
        'pending' => ['warning', 'Menunggu'],
        'confirmed' => ['success', 'Terkonfirmasi'],
        'converted' => ['brand', 'Dikonversi'],
        'reversed' => ['danger', 'Dibatalkan'],
        'active' => ['success', 'Aktif'],
        'inactive' => ['muted', 'Nonaktif'],
        'expired' => ['muted', 'Berakhir'],
        'terminated' => ['danger', 'Dihentikan'],
        'open' => ['danger', 'Baru'],
        'assigned' => ['info', 'Ditugaskan'],
        'in_progress' => ['warning', 'Dikerjakan'],
        'waiting' => ['champagne', 'Menunggu'],
        'completed' => ['success', 'Selesai'],
        'queued' => ['info', 'Dalam antrean'],
        'sent' => ['success', 'Terkirim'],
        'failed' => ['danger', 'Gagal'],
    ];

    [$variant, $label] = $map[$status] ?? ['neutral', ucfirst(str_replace('_', ' ', (string) $status))];
@endphp

<x-ui.badge :variant="$variant" :label="$label" />
