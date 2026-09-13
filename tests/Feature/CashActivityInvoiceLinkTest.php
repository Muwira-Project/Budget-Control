<?php

namespace Tests\Feature;

use App\Livewire\Cashflows\Create as CreateCashflow;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashActivityInvoiceLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_in_with_project_ar_invoice_supports_partial_payment(): void
    {
        $user = User::factory()->create();
        $account = CashAccount::factory()->create();
        $project = Project::factory()->create(['kode' => 'PRJ-LINK-01']);
        $ar = Receivable::factory()->create([
            'project_id' => $project->id,
            'nominal' => 10_000_000,
            'nominal_dibayar' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(CreateCashflow::class, ['mode' => 'masuk'])
            ->set('scope', 'project')
            ->set('projectId', $project->id)
            ->set('receivableId', $ar->id)
            ->assertSet('nominal', '10000000') // Auto-filled from sisa
            ->set('nominal', '4000000') // Partial payment
            ->set('cashAccountId', $account->id)
            ->set('keterangan', 'Pembayaran termin pertama')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('cashflows.index'));

        // Sisa piutang berkurang (nominal_dibayar bertambah 4jt)
        $this->assertSame(4_000_000.0, (float) $ar->fresh()->nominal_dibayar);
        $this->assertSame(6_000_000.0, (float) $ar->fresh()->sisa);
        $this->assertSame('sebagian', $ar->fresh()->status->value);

        // Payment record dibuat
        $this->assertDatabaseHas('payments', [
            'receivable_id' => $ar->id,
            'jenis' => 'masuk',
            'nominal' => 4_000_000,
        ]);

        // Cashflow record dibuat
        $this->assertDatabaseHas('cashflows', [
            'jenis' => 'masuk',
            'sumber' => 'pelunasan_ar',
            'nominal' => 4_000_000,
            'cash_account_id' => $account->id,
        ]);
    }

    public function test_cash_out_with_project_ap_invoice_updates_payable_and_creates_cashflow(): void
    {
        $user = User::factory()->create();
        $account = CashAccount::factory()->create();
        $project = Project::factory()->create(['kode' => 'PRJ-AP-01']);
        $ap = Payable::factory()->create([
            'project_id' => $project->id,
            'nominal' => 7_500_000,
            'nominal_dibayar' => 0,
        ]);

        Livewire::actingAs($user)
            ->test(CreateCashflow::class, ['mode' => 'keluar'])
            ->set('scope', 'project')
            ->set('projectId', $project->id)
            ->set('payableId', $ap->id)
            ->assertSet('nominal', '7500000') // Auto-filled
            ->set('cashAccountId', $account->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('cashflows.index'));

        // Saldo hutang lunas
        $this->assertSame(7_500_000.0, (float) $ap->fresh()->nominal_dibayar);
        $this->assertSame(0.0, (float) $ap->fresh()->sisa);
        $this->assertSame('lunas', $ap->fresh()->status->value);

        $this->assertDatabaseHas('payments', [
            'payable_id' => $ap->id,
            'jenis' => 'keluar',
            'nominal' => 7_500_000,
        ]);

        $this->assertDatabaseHas('cashflows', [
            'jenis' => 'keluar',
            'sumber' => 'pelunasan_ap',
            'nominal' => 7_500_000,
            'cash_account_id' => $account->id,
        ]);
    }

    public function test_fully_paid_invoices_are_hidden_from_dropdown(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        // Invoice lunas
        $paidAr = Receivable::factory()->create([
            'project_id' => $project->id,
            'nominal' => 5_000_000,
            'nominal_dibayar' => 5_000_000,
        ]);

        // Invoice belum lunas
        $unpaidAr = Receivable::factory()->create([
            'project_id' => $project->id,
            'nominal' => 5_000_000,
            'nominal_dibayar' => 2_000_000,
        ]);

        $component = Livewire::actingAs($user)
            ->test(CreateCashflow::class, ['mode' => 'masuk'])
            ->set('scope', 'project')
            ->set('projectId', $project->id);

        $dropdownList = $component->instance()->outstandingReceivables();

        $this->assertFalse($dropdownList->contains('id', $paidAr->id), 'Invoice lunas tidak boleh muncul di dropdown');
        $this->assertTrue($dropdownList->contains('id', $unpaidAr->id), 'Invoice belum lunas harus muncul di dropdown');
    }

    public function test_non_project_direct_cashflow_creates_cashflow_without_payment(): void
    {
        $user = User::factory()->create();
        $account = CashAccount::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateCashflow::class, ['mode' => 'masuk'])
            ->set('scope', 'non_project')
            ->set('linkMode', 'direct')
            ->set('tanggal', '2026-08-01')
            ->set('nominal', '1500000')
            ->set('cashAccountId', $account->id)
            ->set('keterangan', 'Pendapatan non-project langsung')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('cashflows.index'));

        // Cashflow dibuat
        $this->assertDatabaseHas('cashflows', [
            'jenis' => 'masuk',
            'sumber' => 'pendapatan',
            'nominal' => 1_500_000,
            'cash_account_id' => $account->id,
        ]);

        // Tidak ada payment yang dibuat
        $this->assertSame(0, Payment::count());
    }
}
