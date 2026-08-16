<?php

namespace Tests\Feature;

use App\Livewire\Payments\Index as IndexPayment;
use App\Livewire\Receivables\Pay as PayReceivable;
use App\Models\Cashflow;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\Receivable;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('payments.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        $receivable = Receivable::factory()->create();
        Payment::factory()->create(['receivable_id' => $receivable->id]);

        $this->actingAs($user)
            ->get(route('payments.index'))
            ->assertOk();
    }

    public function test_receivable_payment_updates_amount_and_cashflow(): void
    {
        $user = User::factory()->create();
        $receivable = Receivable::factory()->create(['nominal' => 100000000, 'nominal_dibayar' => 0]);

        $payment = app(PaymentService::class)->createForReceivable($receivable, [
            'tanggal' => '2026-07-20',
            'nominal' => 40000000,
            'keterangan' => 'Pelunasan sebagian',
        ]);

        $this->assertSame(40000000.0, (float) $receivable->fresh()->nominal_dibayar);
        $this->assertSame('sebagian', $receivable->fresh()->status->value);
        $this->assertDatabaseHas('payments', ['receivable_id' => $receivable->id, 'jenis' => 'masuk', 'nominal' => 40000000]);
        $this->assertDatabaseHas('cashflows', ['jenis' => 'masuk', 'sumber' => 'pelunasan_ar', 'nominal' => 40000000]);
    }

    public function test_payable_payment_updates_amount_and_cashflow(): void
    {
        $user = User::factory()->create();
        $payable = Payable::factory()->create(['nominal' => 50000000, 'nominal_dibayar' => 0]);

        $payment = app(PaymentService::class)->createForPayable($payable, [
            'tanggal' => '2026-07-22',
            'nominal' => 50000000,
            'keterangan' => 'Pelunasan penuh',
        ]);

        $this->assertSame(50000000.0, (float) $payable->fresh()->nominal_dibayar);
        $this->assertSame('lunas', $payable->fresh()->status->value);
        $this->assertDatabaseHas('payments', ['payable_id' => $payable->id, 'jenis' => 'keluar', 'nominal' => 50000000]);
        $this->assertDatabaseHas('cashflows', ['jenis' => 'keluar', 'sumber' => 'pelunasan_ap', 'nominal' => 50000000]);
        $this->assertDatabaseHas('realisasi', ['sumber' => 'pelunasan_ap', 'sumber_id' => $payment->id]);
    }

    public function test_payment_delete_reverses_amount_and_cashflow(): void
    {
        $user = User::factory()->create();
        $receivable = Receivable::factory()->create(['nominal' => 100000000, 'nominal_dibayar' => 0]);
        $payment = app(PaymentService::class)->createForReceivable($receivable, [
            'tanggal' => '2026-07-20',
            'nominal' => 40000000,
            'keterangan' => 'Pelunasan sebagian',
        ]);

        $this->assertSame(1, Cashflow::where('payment_id', $payment->id)->count());

        app(PaymentService::class)->delete($payment);

        $this->assertSame(0.0, (float) $receivable->fresh()->nominal_dibayar);
        $this->assertSame('belum_dibayar', $receivable->fresh()->status->value);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('cashflows', ['payment_id' => $payment->id]);
    }

    public function test_receivable_payment_cannot_exceed_sisa(): void
    {
        $user = User::factory()->create();
        $receivable = Receivable::factory()->create(['nominal' => 100000000, 'nominal_dibayar' => 50000000]);

        Livewire::actingAs($user)
            ->test(PayReceivable::class, ['receivable' => $receivable])
            ->set('tanggal', '2026-07-20')
            ->set('nominal', '60000000')
            ->call('save')
            ->assertHasErrors(['nominal']);

        $this->assertSame(50000000.0, (float) $receivable->fresh()->nominal_dibayar);
    }

    public function test_service_rejects_an_overpayment_even_without_the_livewire_form(): void
    {
        $receivable = Receivable::factory()->create(['nominal' => 100000000, 'nominal_dibayar' => 50000000]);

        $this->expectException(ValidationException::class);

        app(PaymentService::class)->createForReceivable($receivable, [
            'tanggal' => '2026-07-20',
            'nominal' => 60000000,
        ]);
    }

    public function test_payment_requires_admin_approval_to_be_voided(): void
    {
        $staff = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $receivable = Receivable::factory()->create(['nominal' => 100000000, 'nominal_dibayar' => 0]);
        $payment = app(PaymentService::class)->createForReceivable($receivable, [
            'tanggal' => '2026-07-20',
            'nominal' => 40000000,
        ]);

        // Staff meminta pembatalan -> status pending_cancel.
        Livewire::actingAs($staff)
            ->test(IndexPayment::class)
            ->call('requestVoid', $payment->id)
            ->set('voidReason', 'Salah input nominal')
            ->call('confirmVoid');

        $this->assertSame('pending_cancel', $payment->fresh()->status->value);
        $this->assertDatabaseHas('payments', ['id' => $payment->id]);

        // Admin menyetujui pembatalan -> settlement dihapus + saldo dikembalikan.
        Livewire::actingAs($admin)
            ->test(IndexPayment::class)
            ->call('approveVoid', $payment->id);

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
        $this->assertSame(0.0, (float) $receivable->fresh()->nominal_dibayar);
    }
}
