@extends('layouts.app')

@section('title', 'Staf')

@section('content')
<x-ui.page-header title="Staf" subtitle="Kelola role, permission, dan penugasan properti. Prinsip least privilege.">
    <x-slot:actions>
        <a href="{{ route('staff.create') }}" class="btn-primary">+ Staf</a>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card padding="p-0">
    <div class="overflow-x-auto">
        <table class="table-base">
            <thead>
                <tr>
                    <th scope="col">Nama</th>
                    <th scope="col">Email</th>
                    <th scope="col">Role</th>
                    <th scope="col">Properti</th>
                    <th scope="col">Status</th>
                    <th scope="col" class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($memberships as $membership)
                    <tr>
                        <td class="font-medium">{{ $membership->user?->name }}</td>
                        <td class="text-xs">{{ $membership->user?->email }}</td>
                        <td><x-ui.badge variant="champagne" :label="ucfirst($membership->role)" /></td>
                        <td class="text-xs">
                            {{ $membership->properties->isEmpty() ? 'Semua properti' : $membership->properties->pluck('name')->join(', ') }}
                        </td>
                        <td><x-ui.status-badge :status="$membership->status" /></td>
                        <td class="text-right">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('staff.edit', $membership) }}" class="btn-ghost px-2 py-1">Edit</a>
                                @if ($membership->user_id !== auth()->id())
                                    <form method="POST" action="{{ route('staff.toggle', $membership) }}">@csrf<button class="btn-ghost px-2 py-1">{{ $membership->status === 'active' ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection