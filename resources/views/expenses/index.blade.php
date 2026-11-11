@extends('layouts.app')

@section('title', 'Pengeluaran')

@section('content')
<x-ui.page-header title="Pengeluaran" subtitle="Biaya operasional yang mengurangi arus kas.">
    <x-slot:actions>
        <a href="{{ route('expenses.create') }}" class="btn-primary">+ Pengeluaran</a>
    </x-slot:actions>
</x-ui.page-header>

<form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
    <label for="from" class="sr-only">Dari</label>
    <input id="from" type="date" name="from" value="{{ request('from') }}" class="input sm:max-w-[10rem]">
    <label for="to" class="sr-only">Sampai</label>
    <input id="to" type="date" name="to" value="{{ request('to') }}" class="input sm:max-w-[10rem]">
    <label for="category_id" class="sr-only">Kategori</label>
    <select id="category_id" name="category_id" class="input sm:max-w-[14rem]">
        <option value="">Semua kategori</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <button class="btn-secondary" type="submit">Filter</button>
</form>

<div class="mb-4">
    <x-ui.stat label="Total pengeluaran (sesuai filter)" :value="\App\Support\Money::format($total)" tone="warning" />
</div>

<x-ui.card title="Kategori" class="mb-4">
    <form method="POST" action="{{ route('expenses.categories.store') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end">
        @csrf
        <div class="flex-1">
            <x-ui.input label="Nama kategori baru" name="name" required />
        </div>
        <button class="btn-secondary">Tambah kategori</button>
    </form>
</x-ui.card>

@if ($expenses->isEmpty())
    <x-ui.empty-state title="Belum ada pengeluaran" description="Catat biaya operasional untuk perhitungan arus kas." />
@else
    <x-ui.card padding="p-0">
        <div class="overflow-x-auto">
            <table class="table-base">
                <thead>
                    <tr>
                        <th scope="col">Tanggal</th>
                        <th scope="col">Deskripsi</th>
                        <th scope="col">Kategori</th>
                        <th scope="col">Properti</th>
                        <th scope="col" class="text-right">Nominal</th>
                        <th scope="col" class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($expenses as $expense)
                        <tr>
                            <td>{{ $expense->spent_on->translatedFormat('d M Y') }}</td>
                            <td>{{ $expense->description }}</td>
                            <td>{{ $expense->category?->name ?? '—' }}</td>
                            <td>{{ $expense->property?->name ?? 'Umum' }}</td>
                            <td class="tabular text-right">{{ \App\Support\Money::format($expense->amount) }}</td>
                            <td class="text-right">
                                <a href="{{ route('expenses.edit', $expense) }}" class="btn-ghost px-2 py-1">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.pagination :paginator="$expenses" />
@endif
@endsection