<?php

namespace App\Livewire\Users;

use App\Http\Requests\User\StoreUserRequest;
use App\Services\UserService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Create extends Component
{
    use WithFileUploads;

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
    public function mount(): void
    {
        if (! Gate::allows('manageUsers', User::class)) {
            session()->flash('error', 'Only admins can manage users.');

            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    /**
     * Store a newly created user.
     */
    public function save(UserService $service): void
    {
        if (! Gate::allows('manageUsers', User::class)) {
            session()->flash('error', 'Only admins can manage users.');

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
            (new StoreUserRequest)->rules(),
        )->validate();

        $service->create($validated);

        session()->flash('status', 'User created successfully.');

        $this->redirectRoute('users.index', navigate: true);
    }

    /**
     * Render the user create page.
     */
    public function render()
    {
        return view('livewire.users.create');
    }
}
