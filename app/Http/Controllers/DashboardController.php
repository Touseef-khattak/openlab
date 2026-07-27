<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Enums\SampleStatus;
use App\Models\Patient;
use App\Models\Sample;
use App\Models\Visit;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $today = now()->startOfDay();

        $sampleCounts = Sample::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return Inertia::render('dashboard', [
            'stats' => [
                'visits_today' => Visit::where('visited_at', '>=', $today)->count(),
                'revenue_today' => (float) Visit::where('visited_at', '>=', $today)->sum('net_amount'),
                'unpaid_balance' => (float) Visit::where('payment_status', '!=', PaymentStatus::Paid)
                    ->selectRaw('coalesce(sum(net_amount - paid_amount), 0) as balance')
                    ->value('balance'),
                'patients_total' => Patient::count(),
                'pending_collection' => $sampleCounts[SampleStatus::Registered->value] ?? 0,
                'on_bench' => ($sampleCounts[SampleStatus::Collected->value] ?? 0)
                    + ($sampleCounts[SampleStatus::InProgress->value] ?? 0),
                'awaiting_verification' => $sampleCounts[SampleStatus::Resulted->value] ?? 0,
                'reported_today' => Sample::where('reported_at', '>=', $today)->count(),
            ],
            'recentVisits' => Visit::with('patient:id,name,mrn')
                ->latest('visited_at')
                ->limit(8)
                ->get(['id', 'visit_number', 'patient_id', 'visited_at', 'net_amount', 'payment_status']),
        ]);
    }
}
