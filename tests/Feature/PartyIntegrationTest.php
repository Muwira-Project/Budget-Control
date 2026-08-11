<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Investor;
use App\Models\Mandor;
use App\Models\Payable;
use App\Models\PaymentRequest;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Services\ActualService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_actual_from_payment_request_keeps_mandor_party(): void
    {
        $project = Project::factory()->create();
        $akun = $this->allocatedAkun($project);
        $mandor = Mandor::factory()->create();

        $pr = PaymentRequest::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'vendor_id' => null,
            'supplier_id' => null,
            'mandor_id' => $mandor->id,
            'investor_id' => null,
        ]);

        $actual = app(ActualService::class)->recordFromPaymentRequest($pr);

        $this->assertSame('mandor', $actual->pihakJenis);
        $this->assertDatabaseHas('realisasi', [
            'id' => $actual->id,
            'sumber' => Realisasi::SUMBER_PAYMENT_REQUEST,
            'mandor_id' => $mandor->id,
        ]);
    }

    public function test_auto_actual_from_ap_payment_keeps_investor_party(): void
    {
        $project = Project::factory()->create();
        $akun = $this->allocatedAkun($project);
        $investor = Investor::factory()->create();

        $payable = Payable::create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'investor_id' => $investor->id,
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
            'investor_id' => $investor->id,
            'nominal' => 75000000,
        ]);
    }

    public function test_auto_actual_skips_payable_from_payment_request(): void
    {
        $project = Project::factory()->create();
        $akun = $this->allocatedAkun($project);
        $mandor = Mandor::factory()->create();

        $pr = PaymentRequest::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'vendor_id' => null,
            'supplier_id' => null,
            'mandor_id' => $mandor->id,
            'investor_id' => null,
        ]);

        $payable = Payable::create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'payment_request_id' => $pr->id,
            'mandor_id' => $mandor->id,
            'tanggal' => '2026-08-01',
            'nominal' => 75000000,
            'pajak_include' => true,
        ]);

        $payment = app(PaymentService::class)->createForPayable($payable, [
            'tanggal' => '2026-08-01',
            'nominal' => 75000000,
            'keterangan' => 'Lunas',
        ]);

        $this->assertDatabaseMissing('realisasi', ['sumber_id' => $payment->id]);
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
