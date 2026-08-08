<?php

namespace Tests\Feature;

use App\Livewire\Realisasi\Create as CreateRealisasi;
use App\Models\Akun;
use App\Models\Investor;
use App\Models\Mandor;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PartyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_realisasi_can_be_created_with_mandor_party(): void
    {
        $user = User::factory()->create();
        [$project, $akun] = $this->allocatedProjectAkun();
        $mandor = Mandor::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateRealisasi::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('pihakJenis', 'mandor')
            ->set('mandorId', $mandor->id)
            ->set('tanggal', '2026-08-01')
            ->set('nominal', '50000000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('realisasi.index'));

        $this->assertDatabaseHas('realisasi', [
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'mandor_id' => $mandor->id,
            'nominal' => 50000000,
        ]);
    }

    public function test_realisasi_with_investor_generates_payable(): void
    {
        $user = User::factory()->create();
        [$project, $akun] = $this->allocatedProjectAkun();
        $investor = Investor::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateRealisasi::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('pihakJenis', 'investor')
            ->set('investorId', $investor->id)
            ->set('tanggal', '2026-08-01')
            ->set('nominal', '75000000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payables', [
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'investor_id' => $investor->id,
        ]);
    }

    private function allocatedProjectAkun(): array
    {
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'approved']);

        return [$project, $akun];
    }
}
