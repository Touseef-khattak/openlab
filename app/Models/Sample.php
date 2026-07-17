<?php

namespace App\Models;

use App\Enums\SampleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sample extends Model
{
    protected $fillable = [
        'visit_id',
        'sample_number',
        'specimen_type',
        'status',
        'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SampleStatus::class,
            'collected_at' => 'datetime',
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
