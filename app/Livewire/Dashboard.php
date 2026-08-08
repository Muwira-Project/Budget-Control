<?php

namespace App\Livewire;

use App\Services\DashboardService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public ?string $startDate = null;

    public ?string $endDate = null;

    /**
     * Summary statistics for the dashboard, optionally within a date range.
     *
     * @return array<string, int|float|array<string, float>>
     */
    #[Computed]
    public function statistics(): array
    {
        return app(DashboardService::class)->statistics($this->startDate, $this->endDate);
    }

    /**
     * Whether the selected date range is invalid (start after end).
     */
    #[Computed]
    public function dateRangeInvalid(): bool
    {
        return $this->startDate !== null
            && $this->endDate !== null
            && $this->startDate > $this->endDate;
    }

    /**
     * Render the dashboard.
     */
    public function render()
    {
        return view('livewire.dashboard');
    }
}
