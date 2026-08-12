<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LabSetting extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'address',
        'phone',
        'email',
        'report_header',
        'report_footer',
        'receipt_footer',
    ];

    /**
     * The lab's branding profile. There is only ever one row; it is created
     * with sensible defaults on first access.
     */
    public static function current(): self
    {
        return static::firstOrCreate([], ['name' => config('app.name')]);
    }
}
