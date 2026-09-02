<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Payable;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_actual_from_ap_payment_keeps_investor_party(): void
    {
        $project = Project::factory()->create();
        $akun = $this->allocatedAkun($project);
        $investorType = MasterType::firstOrCreate(
            ['kode' => 'INVESTOR'],
            ['nama' => 'Investor', 'flag_ar' => true, 'flag_ap' => true, 'aktif' => true, 'is_system' => true],
        );
        $investor = MasterItem::factory()->create([
            'master_type_id' => $investorType->id,
            'nama' => 'PT Investor Maju',
            'flag_ar' => true,
            'flag_ap' => true,
        ]);

        $payable = Payable::create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'pihak_type_id' => $investorType->id,
            'pihak_item_id' => $investor->id,
            'tanggal' => '2026-08-01',
            'nominal' => 75000000,
            'pajak_include' => true,
        ]);

        $payment = app(PaymentService::class)->createForPayable($payable, [
            'tanggal' => '2026-08-01',
            'nominal' => 75000000,
            'keterangan' => 'Lunas',
        ]);

        $this->assertDatabaseHas('realisasi', [
            'sumber' => Realisasi::SUMBER_AP_PAYMENT,
            'sumber_id' => $payment->id,
            'pihak_type_id' => $investorType->id,
            'pihak_item_id' => $investor->id,
            'nominal' => 75000000,
        ]);
    }

    private function allocatedAkun(Project $project): Akun
    {
        $akun = Akun::factory()->create();

        ProjectAkun::create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'budget' => 100000000,
            'allocation' => 100000000,
        ]);

        return $akun;
    }
}
