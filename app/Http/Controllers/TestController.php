<?php

namespace App\Http\Controllers;

use App\Actions\SaveTest;
use App\Http\Requests\StoreTestRequest;
use App\Models\Test;
use App\Models\TestCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TestController extends Controller
{
    public function index(Request $request): Response
    {
        $tests = Test::query()
            ->with('category:id,name')
            ->withCount('parameters')
            ->when($request->string('q')->trim()->isNotEmpty(), function ($query) use ($request) {
                $q = $request->string('q')->trim();
                $query->where(fn ($w) => $w
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%"));
            })
            ->when($request->filled('active'), fn ($query) => $query->where('is_active', $request->boolean('active')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('tests/index', [
            'tests' => $tests,
            'filters' => [
                'q' => $request->string('q')->trim()->value(),
                'active' => $request->filled('active') ? $request->boolean('active') : null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tests/create', [
            'categories' => TestCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreTestRequest $request, SaveTest $saveTest): RedirectResponse
    {
        $test = $saveTest->handle($request->validated());

        return redirect()->route('tests.index')
            ->with('success', "Test “{$test->name}” created.");
    }

    public function edit(Test $test): Response
    {
        return Inertia::render('tests/edit', [
            'categories' => TestCategory::orderBy('name')->get(['id', 'name']),
            'test' => $this->toForm($test),
        ]);
    }

    public function update(StoreTestRequest $request, Test $test, SaveTest $saveTest): RedirectResponse
    {
        $saveTest->handle($request->validated(), $test);

        return redirect()->route('tests.index')
            ->with('success', "Test “{$test->name}” updated.");
    }

    public function toggle(Test $test): RedirectResponse
    {
        $test->update(['is_active' => ! $test->is_active]);

        return back()->with('success', "“{$test->name}” is now " . ($test->is_active ? 'active' : 'inactive') . '.');
    }

    public function destroy(Test $test): RedirectResponse
    {
        // A test that has been ordered on a visit stays in the catalog for
        // history; deactivate it instead of deleting.
        if ($test->visitTests()->exists()) {
            return back()->with('error', "“{$test->name}” has been ordered before and cannot be deleted — mark it inactive instead.");
        }

        $test->delete();

        return back()->with('success', "Test “{$test->name}” deleted.");
    }

    /**
     * Serialise a test into the shape the create/edit form consumes:
     * gender null → "all", age months → years.
     */
    private function toForm(Test $test): array
    {
        $test->load(['parameters.referenceRanges']);

        return [
            'id' => $test->id,
            'name' => $test->name,
            'code' => $test->code,
            'test_category_id' => $test->test_category_id,
            'specimen_type' => $test->specimen_type,
            'price' => (string) $test->price,
            'is_active' => $test->is_active,
            'is_panel' => $test->is_panel,
            'parameters' => $test->parameters->map(fn ($parameter) => [
                'id' => $parameter->id,
                'name' => $parameter->name,
                'unit' => $parameter->unit,
                'result_type' => $parameter->result_type->value,
                'decimals' => $parameter->decimals,
                'options' => $parameter->options ?? [],
                'has_results' => $parameter->results()->exists(),
                'ranges' => $parameter->referenceRanges->map(fn ($range) => [
                    'gender' => $range->gender?->value ?? 'all',
                    'age_min_years' => $range->age_min_months !== null ? intdiv($range->age_min_months, 12) : null,
                    'age_max_years' => $range->age_max_months !== null ? intdiv($range->age_max_months, 12) : null,
                    'low' => $range->low !== null ? (float) $range->low : null,
                    'high' => $range->high !== null ? (float) $range->high : null,
                    'critical_low' => $range->critical_low !== null ? (float) $range->critical_low : null,
                    'critical_high' => $range->critical_high !== null ? (float) $range->critical_high : null,
                    'normal_text' => $range->normal_text,
                ])->values(),
            ])->values(),
        ];
    }
}
