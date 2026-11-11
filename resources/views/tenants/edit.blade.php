@extends('layouts.app')

@section('title', 'Edit penyewa')

@section('content')
<x-ui.page-header :title="'Edit '.$tenant->full_name" :subtitle="$tenant->code" />

<form method="POST" action="{{ route('tenants.update', $tenant) }}" class="max-w-3xl space-y-4">
    @csrf
    @method('PUT')
    @include('tenants._form', ['tenant' => $tenant])
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Simpan perubahan</button>
        <a href="{{ route('tenants.show', $tenant) }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection
