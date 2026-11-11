@php($user = auth()->user())

@if ($user->isPlatformAdmin())
    <a href="{{ route('platform.organizations') }}" class="nav-item {{ request()->routeIs('platform.organizations*') ? 'nav-item-active' : '' }}">
        <span aria-hidden="true">⌸</span> Organisasi Platform
    </a>
    <a href="{{ route('platform.backups.index') }}" class="nav-item {{ request()->routeIs('platform.backups.*') ? 'nav-item-active' : '' }}">
        <span aria-hidden="true">⛁</span> Backup Database
    </a>
@endif

@if ($user->hasPermission('dashboard.view'))
    <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'nav-item-active' : '' }}">
        <span aria-hidden="true">▦</span> Dashboard
    </a>
@endif

@if ($user->isTenant())
    <a href="{{ route('portal.dashboard') }}" class="nav-item {{ request()->routeIs('portal.dashboard') ? 'nav-item-active' : '' }}">
        <span aria-hidden="true">⌂</span> Portal Saya
    </a>
    <a href="{{ route('portal.notifications') }}" class="nav-item {{ request()->routeIs('portal.notifications') ? 'nav-item-active' : '' }}">
        <span aria-hidden="true">✉</span> Notifikasi
    </a>
@endif

@if ($user->hasPermission('properties.view'))
    <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-white/40">Properti</p>
    <a href="{{ route('properties.index') }}" class="nav-item {{ request()->routeIs('properties.*') ? 'nav-item-active' : '' }}">
        <span aria-hidden="true">▤</span> Properti
    </a>
    <a href="{{ route('rooms.index') }}" class="nav-item {{ request()->routeIs('rooms.*') ? 'nav-item-active' : '' }}">
        <span aria-hidden="true">▭</span> Kamar
    </a>
@endif

@if ($user->hasPermission('tenants.view'))
    <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-white/40">Penyewa</p>
    <a href="{{ route('tenants.index') }}" class="nav-item {{ request()->routeIs('tenants.*') ? 'nav-item-active' : '' }}">
        <span aria-hidden="true">☺</span> Penyewa
    </a>
    <a href="{{ route('agreements.index') }}" class="nav-item {{ request()->routeIs('agreements.*') ? 'nav-item-active' : '' }}">
        <span aria-hidden="true">✎</span> Kontrak Sewa
    </a>
    <a href="{{ route('bookings.index') }}" class="nav-item {{ request()->routeIs('bookings.*') ? 'nav-item-active' : '' }}">
        <span aria-hidden="true">⌛</span> Booking
    </a>
@endif

@if ($user->hasPermission('invoices.view') && ! $user->isTenant())
    <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-white/40">Keuangan</p>
    <a href="{{ route('invoices.index') }}" class="nav-item {{ request()->routeIs('invoices.*') ? 'nav-item-active' : '' }}">
        <span aria-hidden="true">▥</span> Invoice
    </a>
    <a href="{{ route('payments.index') }}" class="nav-item {{ request()->routeIs('payments.*') ? 'nav-item-active' : '' }}">
        <span aria-hidden="true">◈</span> Pembayaran
    </a>
    @if ($user->hasPermission('finance.reports.view'))
        <a href="{{ route('reports.finance') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'nav-item-active' : '' }}">
            <span aria-hidden="true">◔</span> Laporan Keuangan
        </a>
    @endif
    @if ($user->hasPermission('finance.expenses.manage'))
        <a href="{{ route('expenses.index') }}" class="nav-item {{ request()->routeIs('expenses.*') ? 'nav-item-active' : '' }}">
            <span aria-hidden="true">↧</span> Pengeluaran
        </a>
    @endif
@endif

@if ($user->hasPermission('maintenance.view') && ! $user->isTenant())
    <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-white/40">Operasional</p>
    <a href="{{ route('maintenance.index') }}" class="nav-item {{ request()->routeIs('maintenance.*') ? 'nav-item-active' : '' }}">
        <span aria-hidden="true">⚒</span> Pemeliharaan
    </a>
@endif

@if ($user->hasPermission('staff.view') || $user->hasPermission('settings.view') || $user->hasPermission('activity_logs.view'))
    <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-white/40">Administrasi</p>
    @if ($user->hasPermission('staff.view'))
        <a href="{{ route('staff.index') }}" class="nav-item {{ request()->routeIs('staff.*') ? 'nav-item-active' : '' }}">
            <span aria-hidden="true">☷</span> Staf
        </a>
    @endif
    @if ($user->hasPermission('settings.view'))
        <a href="{{ route('settings.edit') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'nav-item-active' : '' }}">
            <span aria-hidden="true">⚙</span> Pengaturan
        </a>
    @endif
    @if ($user->hasPermission('activity_logs.view'))
        <a href="{{ route('activity.index') }}" class="nav-item {{ request()->routeIs('activity.*') ? 'nav-item-active' : '' }}">
            <span aria-hidden="true">≡</span> Log Aktivitas
        </a>
    @endif
@endif
