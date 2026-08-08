<?php

namespace App\Livewire\AuditLog;

use App\Livewire\Concerns\PerPagePagination;
use App\Models\Activity;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use PerPagePagination, WithPagination;

    /**
     * The paginated list of activities.
     */
    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        return Activity::query()
            ->with('user')
            ->latest()
            ->paginate($this->perPage);
    }

    /**
     * Render the audit log page.
     */
    public function render()
    {
        return view('livewire.audit-log.index');
    }
}
