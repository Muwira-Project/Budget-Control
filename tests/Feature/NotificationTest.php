<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\User;
use App\Models\Vendor;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_over_budget_realisasi_notifies_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'approved']);
        $vendor = Vendor::factory()->create();

        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'vendor_id' => $vendor->id,
            'supplier_id' => null,
            'nominal' => 150000000,
        ]);

        $this->assertSame(1, $admin->notifications()->count());
        $notification = $admin->notifications()->first();
        $this->assertSame('Over Budget', $notification->data['title']);
    }

    public function test_new_user_notifies_admins(): void
    {
        $admin = User::factory()->admin()->create();

        app(UserService::class)->create([
            'name' => 'Staff Baru',
            'email' => 'staff.baru@muwira.test',
            'password' => 'rahasia123',
            'role' => 'staff',
        ]);

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame('New User', $admin->notifications()->first()->data['title']);
    }

    public function test_mark_all_as_read_clears_unread(): void
    {
        $admin = User::factory()->admin()->create();
        app(UserService::class)->create([
            'name' => 'Staff Baru',
            'email' => 'staff.baru@muwira.test',
            'password' => 'rahasia123',
            'role' => 'staff',
        ]);

        $this->assertSame(1, $admin->unreadNotifications()->count());

        $admin->unreadNotifications->markAsRead();

        $this->assertSame(0, $admin->unreadNotifications()->count());
        $this->assertSame(1, DatabaseNotification::count());
    }
}
