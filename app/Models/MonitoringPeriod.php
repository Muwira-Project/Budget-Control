<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Database\Factories\MonitoringPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nomor', 'project_id', 'tanggal_mulai', 'tanggal_selesai'])]
class MonitoringPeriod extends Model
{
    /** @use HasFactory<MonitoringPeriodFactory> */
    use HasFactory, LogsActivity;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    /**
     * Get the project that owns the period, or null when global.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Human-readable period label, e.g. "1 Mar 2026 – 14 Mar 2026".
     */
    public function getPeriodeLabelAttribute(): string
    {
        return $this->tanggal_mulai->format('j M Y').' – '.$this->tanggal_selesai->format('j M Y');
    }

    /**
     * Week of the month derived from the period start date (1..5).
     */
    public function getWeekAttribute(): int
    {
        return (int) ceil((int) $this->tanggal_mulai->format('j') / 7);
    }

    /**
     * Month label derived from the period start date, e.g. "March 2026".
     */
    public function getMonthAttribute(): string
    {
        return $this->tanggal_mulai->format('F Y');
    }

    /**
     * Short label used in the activity log.
     */
    protected function activityLabel(): string
    {
        return 'Monitoring Period '.($this->nomor ?: '#'.$this->id);
    }

    /**
     * Get budget numbers for this monitoring period's project.
     */
    public function getBudgetNumberAttribute(): ?string
    {
        if (! $this->project_id) {
            return null;
        }

        return $this->project->budgetPlans()
            ->orderBy('periode')
            ->pluck('nomor')
            ->implode(', ');
    }
}
