<?php

namespace Tests\Feature;

use App\Livewire\Users\Create as CreateUser;
use App\Livewire\Users\Edit as EditUser;
use App\Livewire\Users\Index as IndexUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertOk();
    }

    public function test_user_can_be_created(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->set('name', 'Staff Baru')
            ->set('email', 'staff.baru@muwira.test')
            ->set('password', 'rahasia123')
            ->set('passwordConfirmation', 'rahasia123')
            ->set('role', 'admin')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['name' => 'Staff Baru', 'email' => 'staff.baru@muwira.test', 'role' => 'admin']);

        $created = User::where('email', 'staff.baru@muwira.test')->first();

        $this->assertNotNull($created->email_verified_at);
    }

    public function test_user_factory_defaults_to_staff_role(): void
    {
        $user = User::factory()->create();

        $this->assertSame('staff', $user->role->value);
        $this->assertFalse($user->isAdmin());
    }

    public function test_user_email_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'duplicate@muwira.test']);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->set('name', 'Duplikat')
            ->set('email', 'duplicate@muwira.test')
            ->set('password', 'rahasia123')
            ->set('passwordConfirmation', 'rahasia123')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_user_password_must_be_confirmed(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->set('name', 'Staff')
            ->set('email', 'staff@muwira.test')
            ->set('password', 'rahasia123')
            ->set('passwordConfirmation', 'tidak-sama')
            ->call('save')
            ->assertHasErrors(['password']);
    }

    public function test_user_can_be_updated_without_changing_password(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $originalPassword = $user->password;

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['user' => $user])
            ->set('name', 'Nama Diperbarui')
            ->set('role', 'admin')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $fresh = $user->fresh();
        $this->assertSame('Nama Diperbarui', $fresh->name);
        $this->assertSame('admin', $fresh->role->value);
        $this->assertSame($originalPassword, $fresh->password);
    }

    public function test_user_cannot_delete_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(IndexUser::class)
            ->call('delete', $admin->id);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_user_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(IndexUser::class)
            ->call('delete', $user->id);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_user_can_have_profile_photo_created(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $photo = UploadedFile::fake()->image('profile.jpg');

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->set('name', 'Foto User')
            ->set('email', 'foto.user@muwira.test')
            ->set('password', 'rahasia123')
            ->set('passwordConfirmation', 'rahasia123')
            ->set('role', 'staff')
            ->set('foto', $photo)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $user = User::where('email', 'foto.user@muwira.test')->first();

        $this->assertNotNull($user->foto);
        Storage::disk('public')->assertExists($user->foto);
    }

    public function test_user_can_update_profile_photo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['foto' => 'photos/old.jpg']);
        Storage::disk('public')->put('photos/old.jpg', 'old');
        $newPhoto = UploadedFile::fake()->image('new.jpg');

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['user' => $user])
            ->set('name', $user->name)
            ->set('foto', $newPhoto)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertStringStartsWith('photos/', $user->foto);
        $this->assertStringEndsWith('.jpg', $user->foto);
        Storage::disk('public')->assertExists($user->foto);
        Storage::disk('public')->assertMissing('photos/old.jpg');
    }

    public function test_user_update_without_photo_keeps_existing_photo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['foto' => 'photos/keep.jpg']);
        Storage::disk('public')->put('photos/keep.jpg', 'fake-image');

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['user' => $user])
            ->set('name', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertSame('photos/keep.jpg', $user->foto);
        Storage::disk('public')->assertExists('photos/keep.jpg');
    }

    public function test_user_index_shows_profile_photo(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['foto' => 'photos/avatar.jpg']);
        Storage::disk('public')->put('photos/avatar.jpg', 'fake-image');

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('storage/photos/avatar.jpg');
    }

    public function test_staff_cannot_access_user_management(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->get(route('users.index'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->get(route('users.create'))
            ->assertForbidden();

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($staff)
            ->get(route('users.edit', $user))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'penyusup@muwira.test']);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }

    public function test_admin_cannot_demote_own_role(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['user' => $admin])
            ->set('role', 'staff')
            ->call('save');

        $this->assertSame('admin', $admin->fresh()->role->value);
    }

    public function test_last_admin_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(IndexUser::class)
            ->call('delete', $admin->id);

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin']);
    }
}
