<x-guest-layout title="Atur ulang kata sandi">
    <div class="mb-6">
        <h1 class="text-xl font-semibold text-ink dark:text-dark-ink">Atur ulang kata sandi</h1>
        <p class="mt-1 text-sm text-sage dark:text-dark-sage">Pilih kata sandi baru untuk akun Anda.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-ui.input label="Email" name="email" type="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
        <x-ui.input label="Kata sandi baru" name="password" type="password" required autocomplete="new-password" />
        <x-ui.input label="Ulangi kata sandi baru" name="password_confirmation" type="password" required autocomplete="new-password" />

        <button type="submit" class="btn-primary w-full">Simpan kata sandi</button>
    </form>
</x-guest-layout>
