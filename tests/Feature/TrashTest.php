<?php

namespace Tests\Feature;

use App\Livewire\Payments\Index as PaymentsIndex;
use App\Livewire\Trash\Index as TrashIndex;
use App\Models\CashAccount;
use App\Models\Receivable;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_trash_page_is_admin_only(): void
    {
        $staff = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($staff)->get(route('trash.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('trash.index'))->assertOk();
    }

    public function test_trash_lists_soft_deleted_cash_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        $account = CashAccount::factory()->create(['kode' => 'KAS-X']);
        $account->delete();

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->assertOk()
            ->assertSee('KAS-X');
    }

    public function test_trash_restore_brings_row_back(): void
    {
        $admin = User::factory()->admin()->create();
        $account = CashAccount::factory()->create(['kode' => 'KAS-Y']);
        $account->delete();

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('restore', $account->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('cash_accounts', ['id' => $account->id, 'deleted_at' => null]);
        $this->assertSame(1, CashAccount::count());
    }

    public function test_trash_force_delete_removes_row_permanently(): void
    {
        $admin = User::factory()->admin()->create();
        $account = CashAccount::factory()->create(['kode' => 'KAS-Z']);
        $account->delete();

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->call('forceDelete', $account->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('cash_accounts', ['id' => $account->id]);
    }

    public function test_confirm_reject_void_with_valid_payment_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = Receivable::factory()->create(['nominal' => 100000000, 'nominal_dibayar' => 0]);
        $payment = app(PaymentService::class)->createForReceivable($receivable, [
            'tanggal' => '2026-07-20',
            'nominal' => 40000000,
        ]);

        app(PaymentService::class)->requestVoid($payment, 'Salah input');

        Livewire::actingAs($admin)
            ->test(PaymentsIndex::class)
            ->set('rejectingId', $payment->id)
            ->set('rejectNote', 'Tidak valid')
            ->call('confirmReject')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'active',
            'void_review_note' => 'Tidak valid',
        ]);
    }

    public function test_trash_restore_payment_restores_linked_cashflow(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = Receivable::factory()->create(['nominal' => 100000000, 'nominal_dibayar' => 0]);
        $payment = app(PaymentService::class)->createForReceivable($receivable, [
            'tanggal' => '2026-07-20',
            'nominal' => 40000000,
        ]);

        app(PaymentService::class)->delete($payment);

        $this->assertSoftDeleted('payments', ['id' => $payment->id]);
        $this->assertSoftDeleted('cashflows', ['payment_id' => $payment->id]);

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->set('tab', 'payments')
            ->call('restore', $payment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('cashflows', ['payment_id' => $payment->id, 'deleted_at' => null]);
    }

    public function test_trash_restore_payment_restores_paid_amount_on_receivable(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = Receivable::factory()->create(['nominal' => 100000000, 'nominal_dibayar' => 0]);
        $payment = app(PaymentService::class)->createForReceivable($receivable, [
            'tanggal' => '2026-07-20',
            'nominal' => 40000000,
        ]);

        // Setelah payment dibuat, nominal_dibayar = 40jt.
        $this->assertSame(40000000.0, (float) $receivable->fresh()->nominal_dibayar);

        // Delete payment -> nominal_dibayar kembali 0.
        app(PaymentService::class)->delete($payment);
        $this->assertSame(0.0, (float) $receivable->fresh()->nominal_dibayar);

        // Restore payment -> nominal_dibayar HARUS kembali 40jt.
        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->set('tab', 'payments')
            ->call('restore', $payment->id)
            ->assertHasNoErrors();

        $this->assertSame(40000000.0, (float) $receivable->fresh()->nominal_dibayar);
    }

    public function test_trash_force_delete_payment_removes_cashflow_permanently(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = Receivable::factory()->create(['nominal' => 100000000, 'nominal_dibayar' => 0]);
        $payment = app(PaymentService::class)->createForReceivable($receivable, [
            'tanggal' => '2026-07-20',
            'nominal' => 40000000,
        ]);

        app(PaymentService::class)->delete($payment);

        Livewire::actingAs($admin)
            ->test(TrashIndex::class)
            ->set('tab', 'payments')
            ->call('forceDelete', $payment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('cashflows', ['payment_id' => $payment->id]);
    }
}
