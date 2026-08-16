<?php

namespace Tests\Feature;

use App\Livewire\Cashflows\Create as CreateCashflow;
use App\Livewire\Cashflows\Index as IndexCashflow;
use App\Models\Cashflow;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\CashflowService;
use App\Services\PaymentRequestService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CashflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('cashflows.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        Cashflow::factory()->create();

        $this->actingAs($user)
            ->get(route('cashflows.index'))
            ->assertOk();
    }

    public function test_manual_cash_in_can_be_created(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateCashflow::class)
            ->set('tanggal', '2026-07-05')
            ->set('nominal', '250000000')
            ->set('keterangan', 'Pendapatan termin 1')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('cashflows.index'));

        $this->assertDatabaseHas('cashflows', [
            'jenis' => 'masuk',
            'sumber' => 'pendapatan',
            'nominal' => 250000000,
            'status' => 'draft',
        ]);
    }

    public function test_manual_cash_in_requires_nominal(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateCashflow::class)
            ->set('tanggal', '2026-07-05')
            ->set('nominal', '')
            ->call('save')
            ->assertHasErrors(['nominal']);
    }

    public function test_paid_payment_request_registers_cash_out(): void
    {
        $user = User::factory()->create();
        $pr = PaymentRequest::factory()->create(['status' => 'approved']);

        app(PaymentRequestService::class)->markPaid($pr);

        $this->assertDatabaseHas('payment_requests', ['id' => $pr->id, 'status' => 'paid']);
        $this->assertDatabaseHas('cashflows', [
            'payment_request_id' => $pr->id,
            'jenis' => 'keluar',
            'sumber' => 'payment_request',
            'nominal' => (float) $pr->nominal,
        ]);
    }

    public function test_paid_payment_request_does_not_duplicate_cash_out(): void
    {
        $user = User::factory()->create();
        $pr = PaymentRequest::factory()->create(['status' => 'approved']);
        $service = app(PaymentRequestService::class);

        $service->markPaid($pr);
        $service->markPaid($pr->fresh());

        $this->assertSame(1, Cashflow::where('payment_request_id', $pr->id)->count());
    }

    public function test_payment_request_cashflow_uses_the_paid_date(): void
    {
        Carbon::setTestNow('2026-08-06 10:00:00');
        try {
            $pr = PaymentRequest::factory()->create([
                'status' => 'approved',
                'tanggal' => '2026-07-01',
            ]);

            app(PaymentRequestService::class)->markPaid($pr);

            $this->assertSame(1, Cashflow::query()
                ->where('payment_request_id', $pr->id)
                ->whereDate('tanggal', '2026-08-06')
                ->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_statistics_calculate_masuk_keluar_and_saldo(): void
    {
        $user = User::factory()->create();
        Cashflow::factory()->create(['tanggal' => '2026-07-05', 'jenis' => 'masuk', 'nominal' => 250000000]);
        Cashflow::factory()->create(['tanggal' => '2026-07-06', 'jenis' => 'keluar', 'nominal' => 95000000]);
        Cashflow::factory()->create(['tanggal' => '2026-08-01', 'jenis' => 'masuk', 'nominal' => 50000000]);

        $stats = app(CashflowService::class)->statistics('2026-07-01', '2026-07-31');

        $this->assertSame(250000000.0, $stats['total_masuk']);
        $this->assertSame(95000000.0, $stats['total_keluar']);
        $this->assertSame(155000000.0, $stats['saldo']);
    }

    public function test_manual_entry_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $entry = Cashflow::factory()->create(['payment_request_id' => null, 'status' => 'draft']);

        Livewire::actingAs($user)
            ->test(IndexCashflow::class)
            ->call('delete', $entry->id);

        $this->assertDatabaseMissing('cashflows', ['id' => $entry->id]);
    }

    public function test_payment_request_entry_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $pr = PaymentRequest::factory()->create(['status' => 'paid']);
        $entry = Cashflow::factory()->create(['payment_request_id' => $pr->id, 'jenis' => 'keluar']);

        Livewire::actingAs($user)
            ->test(IndexCashflow::class)
            ->call('delete', $entry->id);

        $this->assertDatabaseHas('cashflows', ['id' => $entry->id]);
    }

    public function test_index_filters_by_jenis(): void
    {
        $user = User::factory()->create();
        Cashflow::factory()->create(['tanggal' => '2026-07-05', 'jenis' => 'masuk', 'nominal' => 1000000, 'keterangan' => 'Pemasukan A']);
        Cashflow::factory()->create(['tanggal' => '2026-07-06', 'jenis' => 'keluar', 'nominal' => 2000000, 'keterangan' => 'Pengeluaran B']);

        Livewire::actingAs($user)
            ->test(IndexCashflow::class)
            ->set('jenisFilter', 'masuk')
            ->assertSee('Pemasukan A')
            ->assertDontSee('Pengeluaran B');
    }
}
