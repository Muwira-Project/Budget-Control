<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class UserService
{
    /**
     * Create a new user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        if (isset($data['foto']) && $data['foto'] instanceof UploadedFile) {
            $data['foto'] = $data['foto']->store('photos', 'public');
        }

        $user = User::create($data);

        $user->forceFill(['email_verified_at' => now()])->save();

        app(NotificationService::class)->notifyAdmins(
            'New User',
            'A new user '.$user->name.' ('.$user->email.') was created.',
            route('users.index'),
        );

        return $user;
    }

    /**
     * Update an existing user.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        if (isset($data['foto']) && $data['foto'] instanceof UploadedFile) {
            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }

            $data['foto'] = $data['foto']->store('photos', 'public');
        } else {
            unset($data['foto']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return $user->refresh();
    }

    /**
     * Delete a user.
     */
    public function delete(User $user): void
    {
        $user->delete();
    }

    /**
     * List users, optionally filtered by search query.
     */
    public function paginate(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            }))
            ->orderBy('name')
            ->paginate($perPage);
    }
}
