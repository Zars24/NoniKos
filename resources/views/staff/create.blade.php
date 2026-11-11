@extends('layouts.app')

@section('title', 'Staf baru')

@section('content')
<x-ui.page-header title="Staf baru" subtitle="Akun dibuat langsung aktif dengan role yang dipilih." />

<form method="POST" action="{{ route('staff.store') }}" class="max-w-2xl space-y-4">
    @csrf
    <div class="card space-y-4 p-5">
        <x-ui.input label="Nama" name="name" required />
        <x-ui.input label="Email" name="email" type="email" required />
        <x-ui.input label="Kata sandi" name="password" type="password" required help="Minimal 8 karakter. Sampaikan ke staf melalui jalur aman." />
        <x-ui.select label="Role" name="role" required placeholder="Pilih role"
            :options="collect($roles)->mapWithKeys(fn ($r) => [$r => ucfirst($r)])->all()" />
        <fieldset>
            <legend class="label">Penugasan properti</legend>
            <p class="hint mb-2">Kosongkan untuk akses seluruh properti organisasi.</p>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($properties as $property)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="property_ids[]" value="{{ $property->id }}" class="rounded border-stone text-brand focus:ring-champagne dark:border-dark-border dark:bg-dark-elevated">
                        {{ $property->name }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    </div>

    <x-ui.card title="Permission per role">
        <div class="overflow-x-auto">
            <table class="table-base text-xs">
                <thead><tr><th scope="col">Permission</th>@foreach ($roles as $role)<th scope="col">{{ ucfirst($role) }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($permissionMatrix as $permission => $allowedRoles)
                        <tr>
                            <td class="font-mono">{{ $permission }}</td>
                            @foreach ($roles as $role)
                                <td class="text-center">{{ in_array($role, $allowedRoles, true) ? '✓' : '·' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Buat akun staf</button>
        <a href="{{ route('staff.index') }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection