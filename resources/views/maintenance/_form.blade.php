<div class="card space-y-4 p-5">
    <x-ui.input label="Judul" name="title" :value="$requestModel->title" required />
    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.select label="Properti" name="property_id" required placeholder="Pilih properti"
            :selected="$requestModel->property_id" :options="$properties->pluck('name', 'id')->all()" />
        <x-ui.select label="Kamar" name="room_id" :selected="$requestModel->room_id"
            :options="$rooms->mapWithKeys(fn ($r) => [$r->id => $r->number.' — '.$r->property?->name])->all()" placeholder="Tidak spesifik" />
        <x-ui.select label="Penyewa pelapor" name="tenant_id" :selected="$requestModel->tenant_id"
            :options="$tenants->pluck('full_name', 'id')->all()" placeholder="Tanpa penyewa" />
        <x-ui.select label="Kategori" name="category" required :selected="$requestModel->category"
            :options="collect(\App\Models\MaintenanceRequest::CATEGORIES)->mapWithKeys(fn ($c) => [$c => ucfirst($c)])->all()" />
        <x-ui.select label="Prioritas" name="priority" required :selected="$requestModel->priority"
            :options="collect(\App\Models\MaintenanceRequest::PRIORITIES)->mapWithKeys(fn ($p) => [$p => ucfirst($p)])->all()" />
        <x-ui.input label="Estimasi biaya" name="estimated_cost" :value="$requestModel->estimated_cost ? number_format($requestModel->estimated_cost, 0, ',', '.') : ''" />
    </div>
    <x-ui.textarea label="Deskripsi" name="description" :value="$requestModel->description" :rows="4" required />
</div>