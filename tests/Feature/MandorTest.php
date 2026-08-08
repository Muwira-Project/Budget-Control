<?php

namespace Tests\Feature;

use App\Livewire\Mandors\Create as CreateMandor;
use App\Livewire\Mandors\Edit as EditMandor;
use App\Livewire\Mandors\Index as IndexMandor;
use App\Models\Akun;
use App\Models\Mandor;
use App\Models\Project;
use App\Models\Realisasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MandorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('mandors.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        Mandor::factory()->create();

        $this->actingAs($user)
            ->get(route('mandors.index'))
            ->assertOk();
    }

    public function test_mandor_can_be_created(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateMandor::class)
            ->set('kode', 'MND-001')
            ->set('nama', 'Budi Santoso')
            ->set('telepon', '0812-111-2001')
            ->set('alamat', 'Jakarta')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('mandors.index'));

        $this->assertDatabaseHas('mandors', ['kode' => 'MND-001', 'nama' => 'Budi Santoso']);
    }

    public function test_mandor_kode_must_be_unique(): void
    {
        $user = User::factory()->create();
        Mandor::factory()->create(['kode' => 'MND-001']);

        Livewire::actingAs($user)
            ->test(CreateMandor::class)
            ->set('kode', 'MND-001')
            ->set('nama', 'Mandor Lain')
            ->call('save')
            ->assertHasErrors(['kode']);
    }

    public function test_mandor_can_be_updated(): void
    {
        $user = User::factory()->create();
        $mandor = Mandor::factory()->create();

        Livewire::actingAs($user)
            ->test(EditMandor::class, ['mandor' => $mandor])
            ->set('nama', 'Agus Salim')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('mandors.index'));

        $this->assertDatabaseHas('mandors', ['id' => $mandor->id, 'nama' => 'Agus Salim']);
    }

    public function test_mandor_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $mandor = Mandor::factory()->create();

        Livewire::actingAs($user)
            ->test(IndexMandor::class)
            ->call('delete', $mandor->id);

        $this->assertDatabaseMissing('mandors', ['id' => $mandor->id]);
    }

    public function test_mandor_used_by_realisasi_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $mandor = Mandor::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'mandor_id' => $mandor->id,
            'vendor_id' => null,
            'supplier_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(IndexMandor::class)
            ->call('delete', $mandor->id);

        $this->assertDatabaseHas('mandors', ['id' => $mandor->id]);
    }
}
