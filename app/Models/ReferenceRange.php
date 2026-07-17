<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\ResultFlag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferenceRange extends Model
{
    protected $fillable = [
        'test_parameter_id',
        'gender',
        'age_min_months',
        'age_max_months',
        'low',
        'high',
        'critical_low',
        'critical_high',
        'normal_text',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'low' => 'float',
            'high' => 'float',
            'critical_low' => 'float',
            'critical_high' => 'float',
        ];
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(TestParameter::class, 'test_parameter_id');
    }

    public function flagFor(float $value): ResultFlag
    {
        return match (true) {
            $this->critical_low !== null && $value <= $this->critical_low => ResultFlag::CriticalLow,
            $this->critical_high !== null && $value >= $this->critical_high => ResultFlag::CriticalHigh,
            $this->low !== null && $value < $this->low => ResultFlag::Low,
            $this->high !== null && $value > $this->high => ResultFlag::High,
            default => ResultFlag::Normal,
        };
    }
}
