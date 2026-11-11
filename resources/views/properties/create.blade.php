@extends('layouts.app')

@section('title', 'Properti baru')

@section('content')
<x-ui.page-header title="Properti baru" subtitle="Data dasar properti dapat diubah kapan saja." />

<form method="POST" action="{{ route('properties.store') }}" class="max-w-3xl space-y-4">
    @csrf
    @include('properties._form', ['property' => $property])
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Simpan properti</button>
        <a href="{{ route('properties.index') }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection
