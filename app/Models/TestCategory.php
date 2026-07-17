<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestCategory extends Model
{
    protected $fillable = [
        'name',
        'display_order',
    ];

    public function tests(): HasMany
    {
        return $this->hasMany(Test::class);
    }
}
