<?php

namespace App\Http\Controllers;

use App\Models\MessageTemplate;
use App\Services\ActivityLogger;
use App\Services\Integrations\PaymentGateway;
use App\Services\Integrations\WhatsAppSender;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit(PaymentGateway $gateway, WhatsAppSender $whatsapp)
    {
        $organization = auth()->user()->activeMembership()->organization;

        return view('settings.edit', [
            'organization' => $organization,
            'gatewayLive' => $gateway->isLive(),
            'whatsappLive' => $whatsapp->isLive(),
            'templates' => MessageTemplate::orderBy('key')->get(),
        ]);
    }

    public function update(Request $request)
    {
        $organization = $request->user()->activeMembership()->organization;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'invoice_prefix' => ['required', 'string', 'max:12', 'alpha_num'],
            'due_days' => ['required', 'integer', 'min:0', 'max:90'],
            'late_fee_percent' => ['required', 'integer', 'min:0', 'max:50'],
            'timezone' => ['required', 'string', 'max:64'],
        ]);

        $organization->update($data);

        app(ActivityLogger::class)->log('settings.updated', $organization, 'Pengaturan organisasi diperbarui', [
            'due_days' => $data['due_days'],
            'late_fee_percent' => $data['late_fee_percent'],
        ]);

        return back()->with('status', 'Pengaturan disimpan.');
    }

    public function updateTemplate(Request $request, MessageTemplate $template)
    {
        abort_unless($template->organization_id === TenantContext::id(), 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $template->update([
            'name' => $data['name'],
            'body' => $data['body'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('status', 'Template pesan disimpan.');
    }
}