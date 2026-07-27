<?php

namespace App\Models;

use App\Enums\ResultFlag;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    use RecordsActivity;

    protected $fillable = [
        'visit_test_id',
        'test_parameter_id',
        'value',
        'value_numeric',
        'flag',
        'entered_by',
        'entered_at',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'value_numeric' => 'float',
            'flag' => ResultFlag::class,
            'entered_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function visitTest(): BelongsTo
    {
        return $this->belongsTo(VisitTest::class);
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(TestParameter::class, 'test_parameter_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
