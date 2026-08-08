<?php

namespace Tests\Feature;

use App\Livewire\Investors\Create as CreateInvestor;
use App\Livewire\Investors\Edit as EditInvestor;
use App\Livewire\Investors\Index as IndexInvestor;
use App\Models\Akun;
use App\Models\Investor;
use App\Models\Project;
use App\Models\Realisasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvestorTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('investors.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        Investor::factory()->create();

        $this->actingAs($user)
            ->get(route('investors.index'))
            ->assertOk();
    }

    public function test_investor_can_be_created(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateInvestor::class)
            ->set('kode', 'INV-001')
            ->set('nama', 'PT Mitra Investama')
            ->set('telepon', '021-5553001')
            ->set('alamat', 'Jakarta')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('investors.index'));

        $this->assertDatabaseHas('investors', ['kode' => 'INV-001', 'nama' => 'PT Mitra Investama']);
    }

    public function test_investor_kode_must_be_unique(): void
    {
        $user = User::factory()->create();
        Investor::factory()->create(['kode' => 'INV-001']);

        Livewire::actingAs($user)
            ->test(CreateInvestor::class)
            ->set('kode', 'INV-001')
            ->set('nama', 'Investor Lain')
            ->call('save')
            ->assertHasErrors(['kode']);
    }

    public function test_investor_can_be_updated(): void
    {
        $user = User::factory()->create();
        $investor = Investor::factory()->create();

        Livewire::actingAs($user)
            ->test(EditInvestor::class, ['investor' => $investor])
            ->set('nama', 'Tuan Hartono')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('investors.index'));

        $this->assertDatabaseHas('investors', ['id' => $investor->id, 'nama' => 'Tuan Hartono']);
    }

    public function test_investor_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $investor = Investor::factory()->create();

        Livewire::actingAs($user)
            ->test(IndexInvestor::class)
            ->call('delete', $investor->id);

        $this->assertDatabaseMissing('investors', ['id' => $investor->id]);
    }

    public function test_investor_used_by_realisasi_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $investor = Investor::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'investor_id' => $investor->id,
            'vendor_id' => null,
            'supplier_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(IndexInvestor::class)
            ->call('delete', $investor->id);

        $this->assertDatabaseHas('investors', ['id' => $investor->id]);
    }
}
