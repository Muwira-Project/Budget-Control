<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('audit-log.index'))->assertRedirect(route('login'));
    }

    public function test_audit_log_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('audit-log.index'))
            ->assertOk()
            ->assertSee('Audit Log');
    }

    public function test_creating_a_project_records_an_activity(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['kode' => 'PRJ-001']);

        $this->assertDatabaseHas('activities', [
            'user_id' => null,
            'subject_type' => $project->getMorphClass(),
            'subject_id' => $project->id,
            'action' => 'created',
            'description' => 'Created Project PRJ-001',
        ]);
    }

    public function test_updating_a_project_records_an_activity(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $project->update(['nama' => 'Nama Baru']);

        $this->assertDatabaseHas('activities', [
            'subject_type' => $project->getMorphClass(),
            'subject_id' => $project->id,
            'action' => 'updated',
        ]);
    }

    public function test_deleting_an_akun_records_an_activity(): void
    {
        $user = User::factory()->create();
        $akun = Akun::factory()->create(['kode_akun' => 'AKN-001']);

        $akun->delete();

        $this->assertDatabaseHas('activities', [
            'subject_type' => $akun->getMorphClass(),
            'subject_id' => $akun->id,
            'action' => 'deleted',
            'description' => 'Deleted Account AKN-001',
        ]);
    }

    public function test_activity_records_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        Project::factory()->create();

        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'action' => 'created',
        ]);
    }
}
