<?php

namespace App\Http\Controllers;

use App\Enums\SampleStatus;
use App\Models\LabSetting;
use App\Models\Sample;
use App\Models\Visit;
use App\Models\VisitTest;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    // ---------------------------------------------------------------------
    // Clinical lab report (patient PDF) — unchanged, gated by reports.view
    // ---------------------------------------------------------------------

    public function labReport(Visit $visit)
    {
        $visit->load([
            'patient',
            'doctor:id,name,specialty',
            'samples' => fn ($q) => $q->whereIn('status', [SampleStatus::Verified, SampleStatus::Reported]),
            'samples.visitTests.test:id,code,name',
            'samples.visitTests.test.parameters.referenceRanges',
            'samples.visitTests.results.verifiedBy:id,name',
        ]);

        abort_if($visit->samples->isEmpty(), 409, 'No verified results to report yet.');

        $patient = $visit->patient;

        $sections = $visit->samples->map(fn (Sample $sample) => [
            'sample_number' => $sample->sample_number,
            'specimen_type' => $sample->specimen_type,
            'collected_at' => $sample->collected_at?->format('d M Y H:i'),
            'tests' => $sample->visitTests->map(function ($visitTest) use ($patient) {
                return [
                    'code' => $visitTest->test->code,
                    'name' => $visitTest->test->name,
                    'parameters' => $visitTest->test->parameters->map(function ($parameter) use ($visitTest, $patient) {
                        $range = $parameter->rangeFor($patient);
                        $result = $visitTest->results->firstWhere('test_parameter_id', $parameter->id);

                        return [
                            'name' => $parameter->name,
                            'value' => $result?->value,
                            'flag' => $result?->flag?->value,
                            'unit' => $parameter->unit,
                            'range_low' => $range?->low,
                            'range_high' => $range?->high,
                            'normal_text' => $range?->normal_text,
                        ];
                    })->values(),
                ];
            })->values(),
        ])->values();

        $verifier = $visit->samples
            ->flatMap(fn ($sample) => $sample->visitTests->flatMap->results)
            ->first(fn ($result) => $result->verifiedBy !== null);

        $pdf = Pdf::loadView('reports.lab-report', [
            'branding' => LabSetting::current(),
            'visit' => $visit,
            'patient' => $patient,
            'sections' => $sections,
            'verifierName' => $verifier?->verifiedBy?->name,
            'verifiedAt' => $verifier?->verified_at?->format('d M Y H:i'),
        ])->setPaper('a4');

        Sample::query()
            ->where('visit_id', $visit->id)
            ->where('status', SampleStatus::Verified)
            ->update(['status' => SampleStatus::Reported, 'reported_at' => now()]);

        return $pdf->stream("{$visit->visit_number}-report.pdf");
    }

    // ---------------------------------------------------------------------
    // Business / MIS reports — gated by reports.business (admin)
    // ---------------------------------------------------------------------

    public function index()
    {
        return inertia('reports/index');
    }

    public function dailyCollection(Request $request): mixed
    {
        $date = CarbonImmutable::parse($request->date('date') ?? CarbonImmutable::now())->startOfDay();

        $visits = Visit::with('patient:id,name,mrn')
            ->whereBetween('visited_at', [$date, $date->endOfDay()])
            ->orderBy('visited_at')
            ->get();

        $rows = $visits->map(fn (Visit $visit) => [
            'visit_number' => $visit->visit_number,
            'time' => $visit->visited_at->format('H:i'),
            'patient' => $visit->patient->name,
            'mrn' => $visit->patient->mrn,
            'gross' => (float) $visit->total_amount,
            'discount' => (float) $visit->discount,
            'net' => (float) $visit->net_amount,
            'paid' => (float) $visit->paid_amount,
            'balance' => (float) $visit->net_amount - (float) $visit->paid_amount,
            'payment_status' => $visit->payment_status->value,
        ])->values();

        if ($request->query('format') === 'csv') {
            return $this->streamCsv(
                "daily-collection-{$date->format('Y-m-d')}.csv",
                ['Visit', 'Time', 'Patient', 'MRN', 'Gross', 'Discount', 'Net', 'Paid', 'Balance', 'Status'],
                $rows->map(fn ($r) => array_values($r)),
            );
        }

        return inertia('reports/daily-collection', [
            'date' => $date->format('Y-m-d'),
            'rows' => $rows,
            'summary' => [
                'count' => $rows->count(),
                'gross' => (float) $visits->sum('total_amount'),
                'discount' => (float) $visits->sum('discount'),
                'net' => (float) $visits->sum('net_amount'),
                'collected' => (float) $visits->sum('paid_amount'),
                'outstanding' => (float) $visits->sum(fn ($v) => $v->net_amount - $v->paid_amount),
            ],
            'byStatus' => $visits->groupBy(fn ($v) => $v->payment_status->value)
                ->map(fn ($group) => $group->count()),
        ]);
    }

    public function revenue(Request $request): mixed
    {
        [$from, $to] = $this->range($request);

        $visits = Visit::whereBetween('visited_at', [$from, $to])
            ->orderBy('visited_at')
            ->get(['id', 'visited_at', 'total_amount', 'discount', 'net_amount', 'paid_amount']);

        // Daily series (grouped in PHP so it works on both MySQL and SQLite).
        $daily = $visits
            ->groupBy(fn ($v) => $v->visited_at->format('Y-m-d'))
            ->map(fn ($group, $day) => [
                'date' => $day,
                'visits' => $group->count(),
                'gross' => (float) $group->sum('total_amount'),
                'discount' => (float) $group->sum('discount'),
                'net' => (float) $group->sum('net_amount'),
                'collected' => (float) $group->sum('paid_amount'),
            ])
            ->sortKeys()
            ->values();

        $byCategory = VisitTest::query()
            ->join('visits', 'visits.id', '=', 'visit_tests.visit_id')
            ->leftJoin('tests', 'tests.id', '=', 'visit_tests.test_id')
            ->leftJoin('test_categories', 'test_categories.id', '=', 'tests.test_category_id')
            ->whereBetween('visits.visited_at', [$from, $to])
            ->groupBy('test_categories.id', 'test_categories.name')
            ->selectRaw("coalesce(test_categories.name, 'Uncategorized') as category, count(*) as tests, sum(visit_tests.price) as revenue")
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($r) => ['category' => $r->category, 'tests' => (int) $r->tests, 'revenue' => (float) $r->revenue]);

        if ($request->query('format') === 'csv') {
            return $this->streamCsv(
                "revenue-{$from->format('Y-m-d')}_{$to->format('Y-m-d')}.csv",
                ['Date', 'Visits', 'Gross', 'Discount', 'Net', 'Collected'],
                $daily->map(fn ($r) => array_values($r)),
            );
        }

        return inertia('reports/revenue', [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'daily' => $daily,
            'byCategory' => $byCategory->values(),
            'totals' => [
                'visits' => $visits->count(),
                'gross' => (float) $visits->sum('total_amount'),
                'discount' => (float) $visits->sum('discount'),
                'net' => (float) $visits->sum('net_amount'),
                'collected' => (float) $visits->sum('paid_amount'),
                'outstanding' => (float) $visits->sum(fn ($v) => $v->net_amount - $v->paid_amount),
            ],
        ]);
    }

    public function volumeTat(Request $request): mixed
    {
        [$from, $to] = $this->range($request);

        $volume = VisitTest::query()
            ->join('visits', 'visits.id', '=', 'visit_tests.visit_id')
            ->join('tests', 'tests.id', '=', 'visit_tests.test_id')
            ->whereBetween('visits.visited_at', [$from, $to])
            ->groupBy('tests.id', 'tests.code', 'tests.name')
            ->selectRaw('tests.code as code, tests.name as name, count(*) as orders, sum(visit_tests.price) as revenue')
            ->orderByDesc('orders')
            ->get()
            ->map(fn ($r) => ['code' => $r->code, 'name' => $r->name, 'orders' => (int) $r->orders, 'revenue' => (float) $r->revenue]);

        $tat = $this->turnaround($from, $to);

        // Backlog is a "now" snapshot of everything not yet reported.
        $backlog = Sample::query()
            ->whereNotIn('status', [SampleStatus::Reported])
            ->selectRaw('status, count(*) as total, min(created_at) as oldest')
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => [
                'status' => $r->status instanceof SampleStatus ? $r->status->value : $r->status,
                'total' => (int) $r->total,
                'oldest_hours' => $r->oldest ? round(CarbonImmutable::parse($r->oldest)->diffInHours(), 1) : null,
            ]);

        if ($request->query('format') === 'csv') {
            return $this->streamCsv(
                "test-volume-{$from->format('Y-m-d')}_{$to->format('Y-m-d')}.csv",
                ['Code', 'Test', 'Orders', 'Revenue'],
                $volume->map(fn ($r) => array_values($r)),
            );
        }

        return inertia('reports/volume-tat', [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'volume' => $volume->values(),
            'tat' => $tat,
            'backlog' => $backlog->values(),
        ]);
    }

    public function doctorCommission(Request $request): mixed
    {
        [$from, $to] = $this->range($request);

        $rows = Visit::query()
            ->join('doctors', 'doctors.id', '=', 'visits.doctor_id')
            ->whereNotNull('visits.doctor_id')
            ->whereBetween('visits.visited_at', [$from, $to])
            ->groupBy('doctors.id', 'doctors.name', 'doctors.specialty', 'doctors.commission_percent')
            ->selectRaw('doctors.name as doctor, doctors.specialty as specialty, doctors.commission_percent as percent, count(*) as visits, sum(visits.net_amount) as net')
            ->orderByDesc('net')
            ->get()
            ->map(fn ($r) => [
                'doctor' => $r->doctor,
                'specialty' => $r->specialty,
                'percent' => (float) $r->percent,
                'visits' => (int) $r->visits,
                'net' => (float) $r->net,
                // Commission accrues on the referred net (post-discount) billing.
                'commission' => round((float) $r->net * (float) $r->percent / 100, 2),
            ]);

        if ($request->query('format') === 'csv') {
            return $this->streamCsv(
                "doctor-commission-{$from->format('Y-m-d')}_{$to->format('Y-m-d')}.csv",
                ['Doctor', 'Specialty', 'Commission %', 'Visits', 'Referred Net', 'Commission'],
                $rows->map(fn ($r) => array_values($r)),
            );
        }

        return inertia('reports/doctor-commission', [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'rows' => $rows->values(),
            'totals' => [
                'doctors' => $rows->count(),
                'visits' => $rows->sum('visits'),
                'net' => $rows->sum('net'),
                'commission' => round($rows->sum('commission'), 2),
            ],
        ]);
    }

    /**
     * Average turnaround per stage, over samples reported in the window.
     * Computed in PHP to stay portable across MySQL and the SQLite test DB.
     */
    private function turnaround(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $samples = Sample::query()
            ->whereNotNull('reported_at')
            ->whereBetween('reported_at', [$from, $to])
            ->with('visit:id,visited_at')
            ->get(['id', 'visit_id', 'collected_at', 'reported_at']);

        if ($samples->isEmpty()) {
            return ['samples' => 0, 'registration_to_report_hours' => null, 'collection_to_report_hours' => null];
        }

        $times = DB::table('results')
            ->join('visit_tests', 'visit_tests.id', '=', 'results.visit_test_id')
            ->whereIn('visit_tests.sample_id', $samples->pluck('id'))
            ->groupBy('visit_tests.sample_id')
            ->selectRaw('visit_tests.sample_id as sample_id, max(results.entered_at) as entered_at, max(results.verified_at) as verified_at')
            ->get()
            ->keyBy('sample_id');

        $regToReport = [];
        $colToReport = [];
        $entryToVerify = [];

        foreach ($samples as $sample) {
            $reported = CarbonImmutable::parse($sample->reported_at);
            $regToReport[] = $reported->diffInMinutes($sample->visit->visited_at);

            if ($sample->collected_at) {
                $colToReport[] = $reported->diffInMinutes($sample->collected_at);
            }

            $stamp = $times->get($sample->id);
            if ($stamp && $stamp->entered_at && $stamp->verified_at) {
                $entryToVerify[] = CarbonImmutable::parse($stamp->verified_at)->diffInMinutes($stamp->entered_at);
            }
        }

        $avgHours = fn (array $mins) => $mins === [] ? null : round(array_sum($mins) / count($mins) / 60, 1);

        return [
            'samples' => $samples->count(),
            'registration_to_report_hours' => $avgHours($regToReport),
            'collection_to_report_hours' => $avgHours($colToReport),
            'entry_to_verification_hours' => $avgHours($entryToVerify),
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function range(Request $request): array
    {
        $from = CarbonImmutable::parse($request->date('from') ?? CarbonImmutable::now()->startOfMonth())->startOfDay();
        $to = CarbonImmutable::parse($request->date('to') ?? CarbonImmutable::now())->endOfDay();

        // Guard against a reversed range.
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->startOfDay(), $from->endOfDay()];
        }

        return [$from, $to];
    }

    private function streamCsv(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, is_array($row) ? $row : (array) $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
