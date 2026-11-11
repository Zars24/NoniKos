@extends('layouts.app')

@section('title', 'Edit pengeluaran')

@section('content')
<x-ui.page-header title="Edit pengeluaran" />

<form method="POST" action="{{ route('expenses.update', $expense) }}" class="max-w-xl space-y-4">
    @csrf
    @method('PUT')
    @include('expenses._form', ['expense' => $expense])
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Simpan</button>
        <a href="{{ route('expenses.index') }}" class="btn-secondary">Batal</a>
    </div>
</form>

<div class="mt-4 max-w-xl">
    <x-ui.delete-form :action="route('expenses.destroy', $expense)" method="DELETE" label="Hapus pengeluaran" confirm="Hapus pengeluaran ini?" />
</div>
@endsection