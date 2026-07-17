<?php

namespace App\Enums;

enum SampleStatus: string
{
    case Registered = 'registered';
    case Collected = 'collected';
    case InProgress = 'in_progress';
    case Resulted = 'resulted';
    case Verified = 'verified';
    case Reported = 'reported';

    /**
     * Statuses this one may transition to. Keeps the workflow linear;
     * verification can push a sample back to in_progress for re-testing.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Registered => [self::Collected],
            self::Collected => [self::InProgress, self::Resulted],
            self::InProgress => [self::Resulted],
            self::Resulted => [self::Verified, self::InProgress],
            self::Verified => [self::Reported],
            self::Reported => [],
        };
    }
}
