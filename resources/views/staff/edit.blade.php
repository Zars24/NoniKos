@extends('layouts.app')

@section('title', 'Edit staf')

@section('content')
<x-ui.page-header :title="'Edit '.$membership->user?->name" :subtitle="$membership->user?->email" />

<form method="POST" action="{{ route('staff.update', $membership) }}" class="max-w-2xl space-y-4">
    @csrf
    @method('PUT')
    <div class="card space-y-4 p-5">
        <x-ui.input label="Nama" name="name" :value="$membership->user?->name" required />
        <x-ui.input label="Kata sandi baru (opsional)" name="password" type="password" help="Kosongkan bila tidak diubah." />
        <x-ui.select label="Role" name="role" required :selected="$membership->role"
            :options="collect($roles)->mapWithKeys(fn ($r) => [$r => ucfirst($r)])->all()" />
        <fieldset>
            <legend class="label">Penugasan properti</legend>
            <p class="hint mb-2">Kosongkan untuk akses seluruh properti organisasi.</p>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($properties as $property)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="property_ids[]" value="{{ $property->id }}" @checked($membership->properties->contains($property->id)) class="rounded border-stone text-brand focus:ring-champagne dark:border-dark-border dark:bg-dark-elevated">
                        {{ $property->name }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    </div>
    <div class="flex gap-2">
        <button type="submit" class="btn-primary">Simpan perubahan</button>
        <a href="{{ route('staff.index') }}" class="btn-secondary">Batal</a>
    </div>
</form>
@endsection