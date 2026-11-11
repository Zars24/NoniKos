@extends('layouts.app')

@section('title', 'Laporan pemeliharaan baru')

@section('content')
<x-ui.page-header title="Laporan pemeliharaan baru" />

<form method="POST" action="{{ route('maintenance.store') }}" class="max-w-xl space-y-4">
    @csrf
    @include('maintenance._form', ['requestModel' => $requestModel])
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Simpan laporan</button>
        <a href="{{ route('maintenance.index') }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection