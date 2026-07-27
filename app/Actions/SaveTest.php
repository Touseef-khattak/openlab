<?php

namespace App\Actions;

use App\Enums\ResultType;
use App\Models\Test;
use App\Models\TestParameter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveTest
{
    /**
     * Create or update a test with its parameters and reference ranges.
     *
     * Parameters are synced, not wiped: existing rows are matched by id and
     * updated in place, new rows are created, and removed rows are deleted
     * only when they carry no recorded results (results restrict deletion).
     * Reference ranges cascade, so they are rewritten wholesale per parameter.
     */
    public function handle(array $data, ?Test $test = null): Test
    {
        return DB::transaction(function () use ($data, $test) {
            $isPanel = (bool) $data['is_panel'];

            $attributes = [
                'test_category_id' => $data['test_category_id'] ?? null,
                'code' => filled($data['code'] ?? null)
                    ? $data['code']
                    : $this->generateCode($data['name'], $test?->id),
                'name' => $data['name'],
                'price' => $data['price'] ?? 0,
                'specimen_type' => $data['specimen_type'] ?? null,
                'is_panel' => $isPanel,
                'is_active' => $data['is_active'] ?? true,
            ];

            if ($test) {
                $test->update($attributes);
            } else {
                $test = Test::create($attributes + [
                    'display_order' => (int) (Test::max('display_order') ?? 0) + 1,
                ]);
            }

            $keptIds = [];

            foreach (array_values($data['parameters']) as $order => $parameter) {
                $resultType = $parameter['result_type'] ?? ResultType::Numeric->value;

                $paramAttributes = [
                    // A single-value test's one parameter always mirrors the test name.
                    'name' => $isPanel ? $parameter['name'] : $data['name'],
                    'unit' => $parameter['unit'] ?? null,
                    'result_type' => $resultType,
                    'decimals' => $parameter['decimals'] ?? 2,
                    'options' => $resultType === ResultType::Select->value
                        ? array_values(array_filter(
                            array_map('trim', $parameter['options'] ?? []),
                            fn ($o) => $o !== ''
                        ))
                        : null,
                    'display_order' => $order,
                ];

                if (! empty($parameter['id'])) {
                    /** @var TestParameter $model */
                    $model = $test->parameters()->whereKey($parameter['id'])->firstOrFail();
                    $model->update($paramAttributes);
                } else {
                    $model = $test->parameters()->create($paramAttributes);
                }

                $keptIds[] = $model->id;
                $this->syncRanges($model, $parameter['ranges'] ?? []);
            }

            $this->removeDroppedParameters($test, $keptIds);

            return $test;
        });
    }

    private function syncRanges(TestParameter $parameter, array $ranges): void
    {
        $parameter->referenceRanges()->delete();

        foreach ($ranges as $range) {
            if ($this->rangeIsEmpty($range)) {
                continue;
            }

            $gender = $range['gender'] ?? 'all';

            $parameter->referenceRanges()->create([
                'gender' => $gender === 'all' ? null : $gender,
                'age_min_months' => $this->yearsToMonths($range['age_min_years'] ?? null),
                'age_max_months' => $this->yearsToMonths($range['age_max_years'] ?? null),
                'low' => $this->numeric($range['low'] ?? null),
                'high' => $this->numeric($range['high'] ?? null),
                'critical_low' => $this->numeric($range['critical_low'] ?? null),
                'critical_high' => $this->numeric($range['critical_high'] ?? null),
                'normal_text' => filled($range['normal_text'] ?? null) ? $range['normal_text'] : null,
            ]);
        }
    }

    private function removeDroppedParameters(Test $test, array $keptIds): void
    {
        $dropped = $test->parameters()->whereNotIn('id', $keptIds ?: [0])->get();

        foreach ($dropped as $parameter) {
            if ($parameter->results()->exists()) {
                throw ValidationException::withMessages([
                    'parameters' => "Parameter “{$parameter->name}” has recorded results and cannot be removed.",
                ]);
            }

            $parameter->referenceRanges()->delete();
            $parameter->delete();
        }
    }

    private function generateCode(string $name, ?int $ignoreId): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name)) ?: 'TEST';
        $base = substr($base, 0, 10);

        $code = $base;
        $suffix = 1;
        while (Test::withTrashed()
            ->where('code', $code)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $code = substr($base, 0, 8) . $suffix;
            $suffix++;
        }

        return $code;
    }

    private function rangeIsEmpty(array $range): bool
    {
        foreach (['low', 'high', 'critical_low', 'critical_high', 'normal_text'] as $key) {
            if (filled($range[$key] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function yearsToMonths(mixed $years): ?int
    {
        return filled($years) ? (int) $years * 12 : null;
    }

    private function numeric(mixed $value): ?float
    {
        return filled($value) ? (float) $value : null;
    }
}
