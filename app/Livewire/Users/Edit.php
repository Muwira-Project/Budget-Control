<?php

namespace App\Livewire\Users;

use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Edit extends Component
{
    use WithFileUploads;

    public User $user;

    public string $name = '';

    public ?string $departemen = null;

    public ?string $tanggalMasuk = null;

    public ?string $lokasi = null;

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public string $role = 'staff';

    public ?UploadedFile $foto = null;

    /**
     * Restrict user management to admin accounts.
     */
    public function mount(User $user): void
    {
        if (! Gate::allows('manageUsers', $user)) {
            session()->flash('error', 'Only admins can manage users.');

            $this->redirectRoute('dashboard', navigate: true);

            return;
        }

        $this->user = $user;
        $this->name = $user->name;
        $this->departemen = $user->departemen;
        $this->tanggalMasuk = $user->tanggal_masuk?->format('Y-m-d');
        $this->lokasi = $user->lokasi;
        $this->email = $user->email;
        $this->role = $user->role->value;
    }

    /**
     * Update the user.
     */
    public function save(UserService $service): void
    {
        if (! Gate::allows('manageUsers', $this->user)) {
            session()->flash('error', 'Only admins can manage users.');

            return;
        }

        if ($this->user->id === auth()->id() && $this->role !== 'admin') {
            session()->flash('error', 'You cannot change your own admin role.');

            return;
        }

        $validated = Validator::make(
            [
                'name' => $this->name,
                'departemen' => $this->departemen,
                'tanggal_masuk' => $this->tanggalMasuk,
                'lokasi' => $this->lokasi,
                'foto' => $this->foto,
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->passwordConfirmation,
                'role' => $this->role,
            ],
            (new UpdateUserRequest)->rules($this->user->id),
        )->validate();

        $service->update($this->user, $validated);

        session()->flash('status', 'User updated successfully.');

        $this->redirectRoute('users.index', navigate: true);
    }

    /**
     * Render the user edit page.
     */
    public function render()
    {
        return view('livewire.users.edit');
    }
}
