<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visit extends Model
{
    use HasFactory, RecordsActivity, SoftDeletes;

    protected $fillable = [
        'visit_number',
        'patient_id',
        'doctor_id',
        'visited_at',
        'total_amount',
        'discount',
        'net_amount',
        'paid_amount',
        'payment_status',
        'created_by',
        'ai_summary',
        'ai_summary_provider',
        'ai_summary_model',
        'ai_summary_generated_at',
        'ai_summary_by',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
            'total_amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'payment_status' => PaymentStatus::class,
            'ai_summary_generated_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function summaryAuthor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ai_summary_by');
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class);
    }

    public function visitTests(): HasMany
    {
        return $this->hasMany(VisitTest::class);
    }

    /**
     * Next visit number, sequenced per day: V-250713-001.
     * Call inside the visit-creation transaction.
     */
    public static function nextNumber(): string
    {
        $prefix = 'V-' . now()->format('ymd') . '-';

        $last = static::withTrashed()
            ->where('visit_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('visit_number')
            ->value('visit_number');

        $sequence = $last === null ? 1 : (int) substr($last, strlen($prefix)) + 1;

        return $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
