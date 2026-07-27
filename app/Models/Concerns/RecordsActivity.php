<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Shared clinical audit config: log create/update/delete of the model's
 * fillable attributes, recording only what actually changed, and never
 * emit an "update" entry when nothing did.
 */
trait RecordsActivity
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->getFillable())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName(class_basename($this));
    }
}
