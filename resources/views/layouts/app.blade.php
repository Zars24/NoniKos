<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $title ?? 'NoniKos') — {{ config('app.name') }}</title>
    <script>
        (function () {
            var preference = localStorage.getItem('nonikos-theme') || @js(auth()->user()?->preferredTheme() ?? 'system');
            var dark = preference === 'dark' || (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            if (dark) document.documentElement.classList.add('dark');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans" x-data="themeManager()" x-init="init()">
<a href="#main" class="skip-link">Lewati ke konten utama</a>

<div class="min-h-full lg:flex" x-data="{ sidebarOpen: false }">
    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" x-cloak x-on:click="sidebarOpen = false" class="fixed inset-0 z-30 bg-brand-deep/60 lg:hidden" aria-hidden="true"></div>

    {{-- Sidebar --}}
    <aside
        x-cloak
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 z-40 flex w-64 transform flex-col bg-brand text-white transition-transform duration-200 lg:static lg:translate-x-0 dark:bg-[#101815]"
        aria-label="Navigasi utama"
    >
        <div class="flex h-16 items-center gap-2 border-b border-white/10 px-5">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-champagne text-sm font-bold text-brand-deep">NK</span>
            <div class="leading-tight">
                <p class="text-sm font-semibold tracking-wide">NoniKos</p>
                <p class="text-[11px] text-white/60">Property Management</p>
            </div>
            <button class="ml-auto rounded p-1 text-white/70 hover:text-white lg:hidden" x-on:click="sidebarOpen = false" aria-label="Tutup menu">✕</button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 text-sm">
            @include('layouts.partials.sidebar-nav')
        </nav>

        <div class="border-t border-white/10 px-4 py-3 text-[11px] text-white/50">
            {{ now()->translatedFormat('l, d F Y') }}
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-stone bg-white/95 px-4 backdrop-blur dark:border-dark-border dark:bg-dark-surface/95 sm:px-6">
            <button class="rounded-lg border border-stone p-2 text-ink lg:hidden dark:border-dark-border dark:text-dark-ink" x-on:click="sidebarOpen = true" aria-label="Buka menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <form action="{{ route('search') }}" method="GET" class="hidden min-w-0 flex-1 sm:block" role="search">
                <label for="global-search" class="sr-only">Cari penyewa, kamar, atau invoice</label>
                <input id="global-search" type="search" name="q" value="{{ request('q') }}" placeholder="Cari penyewa, kamar, invoice…" class="input max-w-md" autocomplete="off">
            </form>

            <div class="ml-auto flex items-center gap-2">
                <button type="button" class="rounded-lg border border-stone p-2 text-ink dark:border-dark-border dark:text-dark-ink" x-on:click="set(preference === 'dark' ? 'light' : 'dark')" :aria-label="preference === 'dark' ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'">
                    <span x-show="preference !== 'dark'" aria-hidden="true">☾</span>
                    <span x-show="preference === 'dark'" aria-hidden="true">☀</span>
                </button>

                @php($user = auth()->user())
                @php($memberships = $user->activeMemberships()->with('organization')->get())
                @if ($memberships->count() > 1)
                    <form method="POST" action="{{ route('organization.switch') }}">
                        @csrf
                        <label for="org-switcher" class="sr-only">Pilih organisasi</label>
                        <select id="org-switcher" name="organization_id" onchange="this.form.submit()" class="input py-1.5 text-xs">
                            @foreach ($memberships as $membership)
                                <option value="{{ $membership->organization_id }}" @selected($membership->organization_id === \App\Support\TenantContext::id())>
                                    {{ $membership->organization->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                    <button type="button" x-on:click="open = !open" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-ivory dark:hover:bg-dark-elevated" :aria-expanded="open" aria-haspopup="menu">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand text-xs font-semibold text-white dark:bg-dark-champagne dark:text-dark-bg">
                            {{ Str::upper(Str::substr($user->name, 0, 2)) }}
                        </span>
                        <span class="hidden text-left sm:block">
                            <span class="block text-sm font-medium leading-tight">{{ $user->name }}</span>
                            <span class="block text-[11px] capitalize leading-tight text-sage dark:text-dark-sage">{{ $user->roleName() ?? 'Pengguna' }}</span>
                        </span>
                    </button>
                    <div x-show="open" x-cloak x-transition class="absolute right-0 mt-2 w-52 overflow-hidden rounded-lg border border-stone bg-white py-1 shadow-lift dark:border-dark-border dark:bg-dark-elevated" role="menu">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-ivory dark:hover:bg-dark-surface" role="menuitem">Profil & pengaturan</a>
                        @if ($user->isTenant())
                            <a href="{{ route('portal.dashboard') }}" class="block px-4 py-2 text-sm hover:bg-ivory dark:hover:bg-dark-surface" role="menuitem">Portal penyewa</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-danger hover:bg-ivory dark:hover:bg-dark-surface" role="menuitem">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main id="main" class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <x-ui.alert variant="success" class="mb-4">{{ session('status') }}</x-ui.alert>
            @endif
            @if (session('error'))
                <x-ui.alert variant="danger" class="mb-4">{{ session('error') }}</x-ui.alert>
            @endif
            @isset($slot)
                {{ $slot }}
            @else
                @yield('content')
            @endisset
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
