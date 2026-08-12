<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $activities = Activity::query()
            ->with('causer:id,name')
            ->when($request->filled('log'), fn ($q) => $q->where('log_name', $request->string('log')))
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->string('event')))
            ->when($request->string('q')->trim()->isNotEmpty(), function ($q) use ($request) {
                $term = $request->string('q')->trim();
                $q->where(fn ($w) => $w
                    ->where('description', 'like', "%{$term}%")
                    ->orWhere('subject_type', 'like', "%{$term}%"));
            })
            // id desc = insertion order; deterministic when several events
            // share the same second.
            ->latest('id')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (Activity $activity) => [
                'id' => $activity->id,
                'log_name' => $activity->log_name,
                'event' => $activity->event,
                'description' => $activity->description,
                'subject_type' => $activity->subject_type ? class_basename($activity->subject_type) : null,
                'subject_id' => $activity->subject_id,
                'causer' => $activity->causer?->name,
                'created_at' => $activity->created_at?->toDateTimeString(),
                'changes' => $this->changes($activity),
            ]);

        return Inertia::render('audit/index', [
            'activities' => $activities,
            'filters' => [
                'q' => $request->string('q')->trim()->value(),
                'log' => $request->string('log')->value() ?: null,
                'event' => $request->string('event')->value() ?: null,
            ],
            'logNames' => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name')->filter()->values(),
        ]);
    }

    /**
     * Field-level diff for an activity: on update only the changed fields,
     * on create/delete the recorded attributes.
     */
    private function changes(Activity $activity): array
    {
        $attributes = $activity->properties->get('attributes', []);
        $old = $activity->properties->get('old', []);

        $changes = [];
        foreach ($attributes as $field => $new) {
            $previous = $old[$field] ?? null;
            if ($activity->event === 'updated' && $previous === $new) {
                continue;
            }
            $changes[] = [
                'field' => $field,
                'old' => $this->stringify($previous),
                'new' => $this->stringify($new),
            ];
        }

        return $changes;
    }

    private function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_scalar($value) ? (string) $value : json_encode($value);
    }
}
