@extends('layouts.app')

@section('title', 'Pengeluaran baru')

@section('content')
<x-ui.page-header title="Pengeluaran baru" />

<form method="POST" action="{{ route('expenses.store') }}" class="max-w-xl space-y-4">
    @csrf
    @include('expenses._form', ['expense' => $expense])
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Simpan</button>
        <a href="{{ route('expenses.index') }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection