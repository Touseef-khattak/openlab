<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Test extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'test_category_id',
        'code',
        'name',
        'price',
        'specimen_type',
        'is_panel',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_panel' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TestCategory::class, 'test_category_id');
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(TestParameter::class)->orderBy('display_order');
    }
}
