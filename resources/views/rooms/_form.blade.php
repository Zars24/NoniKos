<div class="card space-y-4 p-5">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.select label="Properti" name="property_id" required :selected="$room->property_id"
            :options="$properties->pluck('name', 'id')->all()" placeholder="Pilih properti" />

        <x-ui.select label="Gedung" name="building_id" :selected="$room->building_id"
            :options="$buildings->mapWithKeys(fn ($b) => [$b->id => $b->name])->all()" placeholder="Tanpa gedung" />

        <x-ui.input label="Nomor kamar" name="number" :value="$room->number" required />

        <x-ui.input label="Lantai" name="floor" type="number" min="0" max="200" :value="$room->floor" />

        <x-ui.select label="Tipe kamar" name="room_type_id" :selected="$room->room_type_id"
            :options="$roomTypes->mapWithKeys(fn ($t) => [$t->id => $t->name])->all()" placeholder="Tanpa tipe" />

        <x-ui.input label="Siklus penagihan (bulan)" name="billing_cycle_months" type="number" min="1" max="24" :value="$room->billing_cycle_months ?? 1" required />

        <x-ui.input label="Harga sewa per bulan" name="monthly_price" :value="$room->monthly_price ? number_format($room->monthly_price, 0, ',', '.') : ''" required help="Contoh: 1.500.000" />

        <x-ui.input label="Deposit" name="deposit_amount" :value="$room->deposit_amount ? number_format($room->deposit_amount, 0, ',', '.') : '0'" />

        @unless ($room->exists)
            <x-ui.select label="Status awal" name="status" :selected="$room->status ?? 'available'"
                :options="collect(\App\Models\Room::STATUSES)->mapWithKeys(fn ($s) => [$s => ucfirst($s)])->all()" />
        @endunless

        <div class="sm:col-span-2">
            <x-ui.textarea label="Fasilitas" name="facilities" :value="$room->facilities" help="Contoh: AC, kasur queen, kamar mandi dalam, WiFi." />
        </div>
        <div class="sm:col-span-2">
            <x-ui.textarea label="Catatan internal" name="notes" :value="$room->notes" />
        </div>
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $room->is_active ?? true)) class="rounded border-stone text-brand focus:ring-champagne dark:border-dark-border dark:bg-dark-elevated">
        Kamar aktif dan dapat disewakan
    </label>
</div>
