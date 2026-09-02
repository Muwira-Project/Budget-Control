<?php

namespace App\Models\Concerns;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    /**
     * Boot the activity logging for the model.
     */
    public static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => $model->recordActivity('created'));
        static::updated(fn (Model $model) => $model->recordActivity('updated'));
        static::deleted(fn (Model $model) => $model->recordActivity('deleted'));
    }

    /**
     * Record an activity entry for the model.
     */
    protected function recordActivity(string $action, array $extraProperties = []): void
    {
        Activity::create([
            'user_id' => auth()->id(),
            'subject_type' => $this->getMorphClass(),
            'subject_id' => $this->getKey(),
            'action' => $action,
            'description' => $this->activityDescription($action, $extraProperties),
            'properties' => array_merge(
                match ($action) {
                    'updated' => [
                        'before' => $this->getOriginal(),
                        'after' => $this->getChanges(),
                    ],
                    default => $this->getAttributes(),
                },
                $extraProperties
            ),
        ]);
    }

    /**
     * Build the human-readable activity description.
     */
    protected function activityDescription(string $action, array $extraProperties = []): string
    {
        return match ($action) {
            'created' => 'Created '.$this->activitySubjectLabel(),
            'updated' => 'Updated '.$this->activitySubjectLabel(),
            'deleted' => 'Deleted '.$this->activitySubjectLabel(),
            'moved_to_revisi' => 'Moved '.$this->activitySubjectLabel().' to Revisi: '.($extraProperties['reason'] ?? 'No reason provided'),
            'exited_revisi' => 'Moved '.$this->activitySubjectLabel().' from Revisi to '.($extraProperties['to_status'] ?? 'Unknown'),
            default => 'Activity '.$this->activitySubjectLabel(),
        };
    }

    /**
     * Build a short label identifying the model instance.
     */
    protected function activitySubjectLabel(): string
    {
        if (method_exists($this, 'activityLabel')) {
            return $this->activityLabel();
        }

        return class_basename($this);
    }
}
