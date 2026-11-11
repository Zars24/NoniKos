<div class="card space-y-4 p-5">
    <x-ui.input label="Tanggal" name="spent_on" type="date" :value="$expense->spent_on?->toDateString()" required />
    <x-ui.input label="Deskripsi" name="description" :value="$expense->description" required />
    <x-ui.input label="Nominal" name="amount" :value="$expense->amount ? number_format($expense->amount, 0, ',', '.') : ''" required help="Contoh: 250.000" />
    <x-ui.select label="Kategori" name="expense_category_id" :selected="$expense->expense_category_id"
        :options="$categories->pluck('name', 'id')->all()" placeholder="Tanpa kategori" />
    <x-ui.select label="Properti" name="property_id" :selected="$expense->property_id"
        :options="$properties->pluck('name', 'id')->all()" placeholder="Pengeluaran umum" />
</div>