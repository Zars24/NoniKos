@extends('layouts.app')

@section('title', 'Penyewa baru')

@section('content')
<x-ui.page-header title="Penyewa baru" subtitle="Kontak darurat membantu saat keadaan mendesak." />

<form method="POST" action="{{ route('tenants.store') }}" class="max-w-3xl space-y-4">
    @csrf
    @include('tenants._form', ['tenant' => $tenant])
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Simpan penyewa</button>
        <a href="{{ route('tenants.index') }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection
