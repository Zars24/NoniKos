@php($isEdit = $property->exists)

<div class="card space-y-4 p-5">
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-ui.input label="Nama properti" name="name" :value="$property->name" required />
        </div>
        <x-ui.input label="Kode internal" name="code" :value="$property->code" help="Contoh: MLT-01" />
        <x-ui.input label="Telepon" name="phone" :value="$property->phone" />
        <div class="sm:col-span-2">
            <x-ui.textarea label="Alamat" name="address" :value="$property->address" />
        </div>
        <x-ui.input label="Kota" name="city" :value="$property->city" />
        <x-ui.input label="Provinsi" name="province" :value="$property->province" />
        <x-ui.input label="Kode pos" name="postal_code" :value="$property->postal_code" />
        <x-ui.input label="Jatuh tempo (hari)" name="default_due_days" type="number" min="0" max="90" :value="$property->default_due_days" help="Kosongkan untuk memakai pengaturan organisasi." />
        <div class="sm:col-span-2">
            <x-ui.textarea label="Deskripsi" name="description" :value="$property->description" />
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $property->is_active ?? true)) class="rounded border-stone text-brand focus:ring-champagne dark:border-dark-border dark:bg-dark-elevated">
        Properti aktif
    </label>
</div>
