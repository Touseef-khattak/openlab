<?php

namespace App\Enums;

enum ResultFlag: string
{
    case Normal = 'normal';
    case Low = 'low';
    case High = 'high';
    case CriticalLow = 'critical_low';
    case CriticalHigh = 'critical_high';

    public function isCritical(): bool
    {
        return $this === self::CriticalLow || $this === self::CriticalHigh;
    }

    public function isAbnormal(): bool
    {
        return $this !== self::Normal;
    }
}
