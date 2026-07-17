<?php

namespace App\Models;

use App\Enums\ResultType;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestParameter extends Model
{
    protected $fillable = [
        'test_id',
        'name',
        'unit',
        'result_type',
        'decimals',
        'options',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'result_type' => ResultType::class,
            'options' => 'array',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function referenceRanges(): HasMany
    {
        return $this->hasMany(ReferenceRange::class);
    }

    /**
     * The reference band that applies to a given patient: most specific
     * match first (gender + age band), falling back to generic bands.
     */
    public function rangeFor(Patient $patient): ?ReferenceRange
    {
        $ageMonths = $patient->ageInMonths();

        return $this->referenceRanges
            ->filter(function (ReferenceRange $range) use ($patient, $ageMonths) {
                $genderOk = $range->gender === null || $range->gender === $patient->gender;
                $minOk = $range->age_min_months === null
                    || ($ageMonths !== null && $ageMonths >= $range->age_min_months);
                $maxOk = $range->age_max_months === null
                    || ($ageMonths !== null && $ageMonths <= $range->age_max_months);

                return $genderOk && $minOk && $maxOk;
            })
            ->sortByDesc(fn (ReferenceRange $range) => ($range->gender !== null ? 2 : 0)
                + ($range->age_min_months !== null || $range->age_max_months !== null ? 1 : 0))
            ->first();
    }
}
