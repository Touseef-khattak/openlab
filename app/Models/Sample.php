<?php

namespace App\Models;

use App\Enums\SampleStatus;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sample extends Model
{
    use RecordsActivity;

    protected $fillable = [
        'visit_id',
        'sample_number',
        'specimen_type',
        'status',
        'collected_at',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SampleStatus::class,
            'collected_at' => 'datetime',
            'reported_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function visitTests(): HasMany
    {
        return $this->hasMany(VisitTest::class);
    }
}
