@extends('layouts.app')

@section('title', 'Kamar baru')

@section('content')
<x-ui.page-header title="Kamar baru" subtitle="Harga kamar disimpan sebagai snapshot pada setiap kontrak." />

<form method="POST" action="{{ route('rooms.store') }}" class="max-w-3xl space-y-4">
    @csrf
    @include('rooms._form', ['room' => $room])
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Simpan kamar</button>
        <a href="{{ route('rooms.index') }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection
