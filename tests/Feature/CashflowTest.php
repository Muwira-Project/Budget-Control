<?php

namespace Tests\Feature;

use App\Livewire\Cashflows\Create as CreateCashflow;
use App\Livewire\Cashflows\Index as IndexCashflow;
use App\Models\Cashflow;
use App\Models\Payment;
use App\Models\User;
use App\Services\CashflowService;
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

    public function test_index_page_renders_with_legacy_payment_request_sumber(): void
    {
        $user = User::factory()->admin()->create();
        // Legacy 'payment_request' values are migrated to 'pengeluaran_lain' by migration 2026_08_18_000002
        // Test that the page renders correctly with valid enum values
        Cashflow::factory()->create([
            'sumber' => 'pengeluaran_lain',
            'jenis' => 'keluar',
            'status' => 'posted',
        ]);

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
            'status' => 'posted',
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
        $entry = Cashflow::factory()->create(['status' => 'posted', 'created_by' => $user->id]);

        Livewire::actingAs($user)
            ->test(IndexCashflow::class)
            ->call('delete', $entry->id);

        $this->assertSoftDeleted('cashflows', ['id' => $entry->id]);
    }

    public function test_deleting_selected_entry_removes_it_from_selected_ids(): void
    {
        $user = User::factory()->create();
        $entry1 = Cashflow::factory()->create(['status' => 'posted', 'jenis' => 'masuk', 'created_by' => $user->id]);
        $entry2 = Cashflow::factory()->create(['status' => 'posted', 'jenis' => 'masuk', 'created_by' => $user->id]);

        $component = Livewire::actingAs($user)
            ->test(IndexCashflow::class)
            ->set('tab', 'cash-in')
            ->set('selectedIds', [$entry1->id])
            ->call('delete', $entry1->id);

        $this->assertSoftDeleted('cashflows', ['id' => $entry1->id]);
        $this->assertDatabaseHas('cashflows', ['id' => $entry2->id, 'deleted_at' => null]);
        $this->assertSame([], $component->get('selectedIds'));
    }

    public function test_manual_entries_can_be_bulk_deleted(): void
    {
        $user = User::factory()->create();
        $entry1 = Cashflow::factory()->create(['status' => 'posted', 'jenis' => 'masuk', 'created_by' => $user->id]);
        $entry2 = Cashflow::factory()->create(['status' => 'posted', 'jenis' => 'masuk', 'created_by' => $user->id]);

        Livewire::actingAs($user)
            ->test(IndexCashflow::class)
            ->set('tab', 'cash-in')
            ->set('selectedIds', [$entry1->id, $entry2->id])
            ->call('deleteSelected');

        $this->assertSoftDeleted('cashflows', ['id' => $entry1->id]);
        $this->assertSoftDeleted('cashflows', ['id' => $entry2->id]);
    }

    public function test_settlement_cashflow_cannot_be_deleted_directly(): void
    {
        $user = User::factory()->create();
        $payment = Payment::factory()->create();
        $entry = Cashflow::factory()->create([
            'status' => 'posted',
            'payment_id' => $payment->id,
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(IndexCashflow::class)
            ->call('delete', $entry->id)
            ->assertSee('Records created automatically from settlements cannot be deleted');

        $this->assertDatabaseHas('cashflows', ['id' => $entry->id, 'deleted_at' => null]);
    }

    public function test_toggle_all_visible_only_selects_manual_entries(): void
    {
        $user = User::factory()->create();
        $manual = Cashflow::factory()->create(['status' => 'posted', 'jenis' => 'masuk', 'created_by' => $user->id]);
        $payment = Payment::factory()->create();
        $auto = Cashflow::factory()->create([
            'status' => 'posted',
            'jenis' => 'masuk',
            'payment_id' => $payment->id,
            'created_by' => $user->id,
        ]);

        $component = Livewire::actingAs($user)
            ->test(IndexCashflow::class)
            ->set('tab', 'cash-in')
            ->call('toggleAllVisible');

        $this->assertSame([$manual->id], $component->get('selectedIds'));
    }

    public function test_index_filters_by_jenis(): void
    {
        $user = User::factory()->create();
        Cashflow::factory()->create(['tanggal' => '2026-07-05', 'jenis' => 'masuk', 'nominal' => 1000000, 'keterangan' => 'Pemasukan A']);
        Cashflow::factory()->create(['tanggal' => '2026-07-06', 'jenis' => 'keluar', 'nominal' => 2000000, 'keterangan' => 'Pengeluaran B']);

        Livewire::actingAs($user)
            ->test(IndexCashflow::class)
            ->set('tab', 'cash-in')
            ->assertSee('Pemasukan A')
            ->assertDontSee('Pengeluaran B');
    }
}
