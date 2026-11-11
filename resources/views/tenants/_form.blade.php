<div class="card space-y-4 p-5">
    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-ui.input label="Nama lengkap" name="full_name" :value="$tenant->full_name" required />
        </div>
        <x-ui.input label="Email" name="email" type="email" :value="$tenant->email" />
        <x-ui.input label="Telepon / WhatsApp" name="phone" :value="$tenant->phone" />
        <x-ui.input label="Nomor identitas" name="identity_number" :value="$tenant->identity_number" help="Disimpan terbatas, hanya untuk verifikasi internal." />
        <x-ui.input label="Tanggal lahir" name="date_of_birth" type="date" :value="$tenant->date_of_birth?->toDateString()" />
        <div class="sm:col-span-2">
            <x-ui.textarea label="Alamat" name="address" :value="$tenant->address" />
        </div>
        <x-ui.input label="Nama kontak darurat" name="emergency_name" :value="$tenant->emergency_name" />
        <x-ui.input label="Telepon kontak darurat" name="emergency_phone" :value="$tenant->emergency_phone" />
        <x-ui.select label="Status" name="status" :selected="$tenant->status"
            :options="['active' => 'Aktif', 'inactive' => 'Nonaktif']" required />
        <div class="sm:col-span-2">
            <x-ui.textarea label="Catatan" name="notes" :value="$tenant->notes" />
        </div>
    </div>
</div>
