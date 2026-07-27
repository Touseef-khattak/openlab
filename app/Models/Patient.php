<?php

namespace App\Models;

use App\Enums\Gender;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, RecordsActivity, SoftDeletes;

    protected $fillable = [
        'mrn',
        'name',
        'cnic',
        'phone',
        'gender',
        'dob',
        'age_years',
    ];

    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'dob' => 'date',
        ];
    }

    public function visits(): HasMany
    {
        return $this->hasMany(Visit::class);
    }

    public function assignMrn(): void
    {
        if ($this->mrn === null) {
            $this->update(['mrn' => sprintf('P%06d', $this->id)]);
        }
    }

    /**
     * Age in whole months, for matching reference-range bands.
     * Falls back to the registration-time age when no dob is stored.
     */
    public function ageInMonths(): ?int
    {
        if ($this->dob !== null) {
            return (int) $this->dob->diffInMonths(now());
        }

        return $this->age_years !== null ? $this->age_years * 12 : null;
    }
}
