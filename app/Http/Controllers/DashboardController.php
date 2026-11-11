<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\ReportService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request, ReportService $reports)
    {
        $from = today()->startOfMonth();
        $to = today()->endOfMonth();
        $summary = $reports->summary($from, $to);

        $roomsByStatus = Room::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        $stats = [
            'properties' => Property::where('is_active', true)->count(),
            'rooms' => $roomsByStatus->sum(),
            'occupied' => (int) ($roomsByStatus['occupied'] ?? 0),
            'available' => (int) ($roomsByStatus['available'] ?? 0),
            'maintenance' => (int) ($roomsByStatus['maintenance'] ?? 0),
            'occupancy' => $roomsByStatus->sum() > 0
                ? round(((int) ($roomsByStatus['occupied'] ?? 0)) / max(1, $roomsByStatus->sum() - (int) ($roomsByStatus['unavailable'] ?? 0)) * 100)
                : 0,
        ];

        $dueSoon = Invoice::with('tenant')
            ->whereNotIn('status', ['paid', 'cancelled', 'draft'])
            ->whereBetween('due_date', [today()->toDateString(), today()->addDays(7)->toDateString()])
            ->orderBy('due_date')
            ->limit(5)->get();

        $recentPayments = Payment::with(['tenant', 'invoice'])
            ->where('status', 'confirmed')
            ->latest('paid_at')->latest('id')->limit(5)->get();

        $endingAgreements = \App\Models\RentalAgreement::with(['tenant', 'room'])
            ->where('status', 'active')
            ->whereBetween('end_date', [today()->toDateString(), today()->addDays(60)->toDateString()])
            ->orderBy('end_date')->limit(5)->get();

        $series = $reports->monthlySeries(today()->subMonths(5)->startOfMonth(), today()->endOfMonth());

        return view('dashboard', compact('stats', 'summary', 'dueSoon', 'recentPayments', 'endingAgreements', 'series'));
    }
}
