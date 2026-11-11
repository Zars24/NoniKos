@extends('layouts.app')

@section('title', 'Edit laporan')

@section('content')
<x-ui.page-header :title="'Edit: '.$requestModel->title" />

<form method="POST" action="{{ route('maintenance.update', $requestModel) }}" class="max-w-xl space-y-4">
    @csrf
    @method('PUT')
    @include('maintenance._form', ['requestModel' => $requestModel])
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Simpan</button>
        <a href="{{ route('maintenance.show', $requestModel) }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection