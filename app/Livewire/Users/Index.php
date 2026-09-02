<?php

namespace App\Livewire\Users;

use App\Livewire\Concerns\BulkSelection;
use App\Livewire\Concerns\PerPagePagination;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use BulkSelection, PerPagePagination, WithPagination;

    public string $search = '';

    /**
     * Restrict user management to admin accounts.
     */
    public function mount(): void
    {
        if (! Gate::allows('manageUsers', User::class)) {
            session()->flash('error', 'Only admins can manage users.');

            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    /**
     * Delete a user.
     */
    public function delete(User $user, UserService $service): void
    {
        if (! Gate::allows('manageUsers', $user)) {
            session()->flash('error', 'Only admins can manage users.');

            return;
        }

        if ($user->id === auth()->id()) {
            session()->flash('error', 'You cannot delete the account currently in use.');

            return;
        }

        if ($user->isAdmin() && User::where('role', 'admin')->count() <= 1) {
            session()->flash('error', 'You cannot delete the last admin.');

            return;
        }

        $service->delete($user);

        session()->flash('status', 'User deleted successfully.');
    }

    /**
     * Reset the pagination when the search query changes.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * The paginated list of users.
     */
    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return app(UserService::class)->paginate($this->search, $this->perPage);
    }

    /**
     * Render the user index page.
     */
    protected function bulkCollectionProperty(): string
    {
        return 'users';
    }

    public function deleteSelected(UserService $service): void
    {
        $deleted = 0;
        $skipped = 0;
        foreach ($this->selectedIds as $id) {
            if (! $user = User::find($id)) {
                continue;
            }
            if (! Gate::allows('manageUsers', $user)
                || $user->id === auth()->id()
                || ($user->isAdmin() && User::where('role', 'admin')->count() <= 1)) {
                $skipped++;

                continue;
            }
            $service->delete($user);
            $deleted++;
        }
        $this->selectedIds = [];
        session()->flash('status', $deleted.' user(s) deleted.');
        if ($skipped > 0) {
            session()->flash('error', $skipped.' user(s) could not be deleted (current account or last admin).');
        }
    }

    public function render()
    {
        return view('livewire.users.index');
    }
}
