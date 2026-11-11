<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantDocument;
use App\Services\ActivityLogger;
use App\Support\CodeGenerator;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Tenant::class);

        $tenants = Tenant::query()
            ->with(['agreements' => fn ($q) => $q->where('status', 'active')->with('room:id,number')])
            ->when($request->query('q'), function ($q, $term) {
                $q->where(function ($inner) use ($term) {
                    $inner->where('full_name', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('code', 'like', "%{$term}%");
                });
            })
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        return view('tenants.index', compact('tenants'));
    }

    public function create()
    {
        $this->authorize('create', Tenant::class);

        return view('tenants.create', ['tenant' => new Tenant(['status' => 'active'])]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Tenant::class);

        $data = $this->validated($request);
        $data['code'] = CodeGenerator::next(Tenant::class, 'TNT');

        $tenant = Tenant::create($data);

        app(ActivityLogger::class)->log('tenant.created', $tenant, 'Penyewa '.$tenant->full_name.' ditambahkan');

        return redirect()->route('tenants.show', $tenant)->with('status', 'Penyewa berhasil ditambahkan.');
    }

    public function show(Tenant $tenant)
    {
        $this->authorize('view', $tenant);

        $tenant->load([
            'agreements' => fn ($q) => $q->with('room:id,number,property_id')->latest('start_date'),
            'invoices' => fn ($q) => $q->latest('period_start')->limit(12),
            'payments' => fn ($q) => $q->latest('paid_at')->limit(12),
            'documents',
        ]);

        return view('tenants.show', compact('tenant'));
    }

    public function edit(Tenant $tenant)
    {
        $this->authorize('update', $tenant);

        return view('tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $this->authorize('update', $tenant);

        $tenant->update($this->validated($request, $tenant));

        app(ActivityLogger::class)->log('tenant.updated', $tenant, 'Data penyewa '.$tenant->full_name.' diperbarui');

        return redirect()->route('tenants.show', $tenant)->with('status', 'Data penyewa diperbarui.');
    }

    public function destroy(Tenant $tenant)
    {
        $this->authorize('delete', $tenant);

        if ($tenant->hasFinancialHistory()) {
            $tenant->update(['status' => 'inactive']);

            return back()->with('status', 'Penyewa memiliki riwayat keuangan sehingga dinonaktifkan, bukan dihapus.');
        }

        $tenant->delete();

        return redirect()->route('tenants.index')->with('status', 'Penyewa dihapus.');
    }

    public function storeDocument(Request $request, Tenant $tenant)
    {
        $this->authorize('update', $tenant);

        $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $path = $request->file('document')->store("tenants/{$tenant->id}/documents", 'private');
        $file = $request->file('document');

        TenantDocument::create([
            'organization_id' => $tenant->organization_id,
            'tenant_id' => $tenant->id,
            'label' => $request->input('label'),
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        app(ActivityLogger::class)->log('tenant.document_uploaded', $tenant, 'Dokumen penyewa diunggah', ['label' => $request->input('label')]);

        return back()->with('status', 'Dokumen diunggah.');
    }

    public function downloadDocument(Tenant $tenant, TenantDocument $document)
    {
        $this->authorize('view', $tenant);

        abort_unless($document->tenant_id === $tenant->id, 404);
        abort_unless(Storage::disk('private')->exists($document->path), 404);

        app(ActivityLogger::class)->log('tenant.document_downloaded', $tenant, 'Dokumen penyewa diunduh', ['label' => $document->label]);

        return Storage::disk('private')->download($document->path, $document->label.'.'.pathinfo($document->path, PATHINFO_EXTENSION));
    }

    public function template()
    {
        $this->authorize('import', Tenant::class);

        $csv = "nama_lengkap,email,telepon,alamat,kontak_darurat,nama_kontak_darurat,catatan\n";
        $csv .= "Budi Santoso,budi@example.test,081200000001,Jl. Melati 1,081200000002,Siti Aminah,Penyewa contoh\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template-import-penyewa.csv"',
        ]);
    }

    /**
     * CSV import with per-row validation and duplicate detection. Nothing is written when any row is invalid.
     */
    public function import(Request $request)
    {
        $this->authorize('import', Tenant::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (! $header) {
            fclose($handle);
            return back()->with('error', 'Berkas CSV tidak memiliki header.');
        }

        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);
        $required = ['nama_lengkap'];
        $missing = array_diff($required, $header);

        if ($missing) {
            fclose($handle);
            return back()->with('error', 'Kolom wajib tidak ditemukan: '.implode(', ', $missing));
        }

        $rows = [];
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $data = array_combine($header, array_pad($row, count($header), null));
            $name = trim((string) ($data['nama_lengkap'] ?? ''));
            $email = trim((string) ($data['email'] ?? ''));

            if ($name === '') {
                $errors[] = "Baris {$line}: nama lengkap wajib diisi.";
                continue;
            }

            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Baris {$line}: format email tidak valid.";
                continue;
            }

            if ($email !== '' && Tenant::where('email', $email)->exists()) {
                $errors[] = "Baris {$line}: email {$email} sudah terdaftar.";
                continue;
            }

            $rows[] = [
                'organization_id' => TenantContext::id(),
                'code' => null,
                'full_name' => $name,
                'email' => $email ?: null,
                'phone' => trim((string) ($data['telepon'] ?? '')) ?: null,
                'address' => trim((string) ($data['alamat'] ?? '')) ?: null,
                'emergency_phone' => trim((string) ($data['kontak_darurat'] ?? '')) ?: null,
                'emergency_name' => trim((string) ($data['nama_kontak_darurat'] ?? '')) ?: null,
                'notes' => trim((string) ($data['catatan'] ?? '')) ?: null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        fclose($handle);

        if ($errors) {
            return back()->with('error', 'Import dibatalkan: '.implode(' ', array_slice($errors, 0, 5)));
        }

        if (! $rows) {
            return back()->with('error', 'Tidak ada baris valid untuk diimpor.');
        }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $row['code'] = CodeGenerator::next(Tenant::class, 'TNT');
                Tenant::create($row);
            }
        });

        app(ActivityLogger::class)->log('tenant.imported', null, count($rows).' penyewa diimpor dari CSV');

        return redirect()->route('tenants.index')->with('status', count($rows).' penyewa berhasil diimpor.');
    }

    protected function validated(Request $request, ?Tenant $tenant = null): array
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('tenants', 'email')->where('organization_id', TenantContext::id())->ignore($tenant?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'identity_number' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:500'],
            'emergency_name' => ['nullable', 'string', 'max:120'],
            'emergency_phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Reject control characters that could break CSV exports or e-mail headers.
        foreach (['full_name', 'email', 'phone'] as $field) {
            if (! empty($data[$field])) {
                $data[$field] = preg_replace('/[\r\n\t]+/', ' ', $data[$field]);
            }
        }

        return $data;
    }
}
