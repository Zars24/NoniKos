@extends('layouts.app')

@section('title', 'Administrasi Platform')

@section('content')
<x-ui.page-header title="Administrasi platform" subtitle="Daftar organisasi yang terdaftar. Data keuangan penyewa tidak ditampilkan di sini." />

<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="search" name="q" value="{{ request('q') }}" class="input max-w-xs" placeholder="Cari nama atau slug…" aria-label="Cari organisasi">
    <select name="status" class="input max-w-[10rem]" aria-label="Filter status">
        <option value="">Semua status</option>
        <option value="active" @selected(request('status') === 'active')>Aktif</option>
        <option value="suspended" @selected(request('status') === 'suspended')>Ditangguhkan</option>
    </select>
    <button class="btn-secondary">Filter</button>
</form>

<x-ui.card padding="p-0">
    <div class="overflow-x-auto">
        <table class="table-base">
            <thead>
                <tr>
                    <th scope="col">Organisasi</th>
                    <th scope="col">Slug</th>
                    <th scope="col" class="text-right">Anggota</th>
                    <th scope="col" class="text-right">Properti</th>
                    <th scope="col">Plan</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($organizations as $organization)
                    <tr>
                        <td class="font-medium">{{ $organization->name }}</td>
                        <td class="font-mono text-xs">{{ $organization->slug }}</td>
                        <td class="tabular text-right">{{ $organization->memberships_count }}</td>
                        <td class="tabular text-right">{{ $organization->properties_count }}</td>
                        <td class="text-xs">{{ $organization->plan }}</td>
                        <td><x-ui.status-badge :status="$organization->status" /></td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('platform.organizations.status', $organization) }}" class="flex justify-end gap-1">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="{{ $organization->status === 'active' ? 'suspended' : 'active' }}">
                                <button class="btn-ghost px-2 py-1 {{ $organization->status === 'active' ? 'text-danger' : '' }}">
                                    {{ $organization->status === 'active' ? 'Tangguhkan' : 'Aktifkan' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-sm text-sage">Tidak ada organisasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>

<x-ui.pagination :paginator="$organizations" />
@endsection