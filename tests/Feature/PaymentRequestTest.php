<?php

namespace Tests\Feature;

use App\Livewire\PaymentRequests\Create as CreatePaymentRequest;
use App\Livewire\PaymentRequests\Edit as EditPaymentRequest;
use App\Livewire\PaymentRequests\Index as IndexPaymentRequest;
use App\Models\Akun;
use App\Models\PaymentRequest;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('payment-requests.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->makePaymentRequest();

        $this->actingAs($user)
            ->get(route('payment-requests.index'))
            ->assertOk();
    }

    public function test_staff_can_create_draft_payment_request(): void
    {
        $user = User::factory()->create();
        [$project, $akun] = $this->allocatedProjectAkun();
        $vendor = Vendor::factory()->create();

        Livewire::actingAs($user)
            ->test(CreatePaymentRequest::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('vendorId', $vendor->id)
            ->set('tanggal', '2026-07-10')
            ->set('jatuhTempo', '2026-08-10')
            ->set('nominal', '60000000')
            ->set('prioritas', 'high')
            ->set('keterangan', 'Upah pekerja')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('payment-requests.index'));

        $this->assertDatabaseHas('payment_requests', [
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'vendor_id' => $vendor->id,
            'nominal' => 60000000,
            'prioritas' => 'high',
            'status' => 'draft',
        ]);
    }

    public function test_payment_request_number_is_generated(): void
    {
        $user = User::factory()->create();
        [$project, $akun] = $this->allocatedProjectAkun();
        $vendor = Vendor::factory()->create();

        Livewire::actingAs($user)
            ->test(CreatePaymentRequest::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('vendorId', $vendor->id)
            ->set('tanggal', '2026-07-10')
            ->set('nominal', '60000000')
            ->set('prioritas', 'medium')
            ->call('save')
            ->assertHasNoErrors();

        $pr = PaymentRequest::first();
        $this->assertMatchesRegularExpression('/^PR-\d{4}-\d{3}$/', $pr->nomor);
    }

    public function test_payment_request_requires_a_party(): void
    {
        $user = User::factory()->create();
        [$project, $akun] = $this->allocatedProjectAkun();

        Livewire::actingAs($user)
            ->test(CreatePaymentRequest::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('tanggal', '2026-07-10')
            ->set('nominal', '60000000')
            ->set('prioritas', 'medium')
            ->call('save')
            ->assertHasErrors(['vendor_id']);
    }

    public function test_payment_request_requires_approved_allocation(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'waiting']);
        $vendor = Vendor::factory()->create();

        Livewire::actingAs($user)
            ->test(CreatePaymentRequest::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('vendorId', $vendor->id)
            ->set('tanggal', '2026-07-10')
            ->set('nominal', '60000000')
            ->set('prioritas', 'medium')
            ->call('save')
            ->assertHasErrors(['akun_id']);
    }

    public function test_draft_can_be_submitted(): void
    {
        $user = User::factory()->create();
        $pr = $this->makePaymentRequest(['status' => 'draft']);
        $pr->update(['created_by' => $user->id]);

        Livewire::actingAs($user)
            ->test(IndexPaymentRequest::class)
            ->call('submit', $pr->id);

        $this->assertSame('waiting', $pr->fresh()->status->value);
    }

    public function test_staff_cannot_approve_payment_request(): void
    {
        $staff = User::factory()->create();
        $pr = $this->makePaymentRequest(['status' => 'waiting']);

        Livewire::actingAs($staff)
            ->test(IndexPaymentRequest::class)
            ->call('approve', $pr->id);

        $this->assertSame('waiting', $pr->fresh()->status->value);
        $this->assertNull($pr->fresh()->approved_by);
    }

    public function test_admin_can_approve_payment_request(): void
    {
        $admin = User::factory()->admin()->create();
        $pr = $this->makePaymentRequest(['status' => 'waiting']);

        Livewire::actingAs($admin)
            ->test(IndexPaymentRequest::class)
            ->call('approve', $pr->id);

        $fresh = $pr->fresh();
        $this->assertSame('approved', $fresh->status->value);
        $this->assertSame($admin->id, $fresh->approved_by);
        $this->assertNotNull($fresh->approved_at);
    }

    public function test_admin_can_reject_payment_request(): void
    {
        $admin = User::factory()->admin()->create();
        $pr = $this->makePaymentRequest(['status' => 'waiting']);

        Livewire::actingAs($admin)
            ->test(IndexPaymentRequest::class)
            ->call('reject', $pr->id);

        $this->assertSame('rejected', $pr->fresh()->status->value);
    }

    public function test_rejected_payment_request_returns_to_draft_after_edit(): void
    {
        $user = User::factory()->admin()->create();
        $pr = $this->makePaymentRequest(['status' => 'rejected', 'nominal' => 50000000]);

        Livewire::actingAs($user)
            ->test(EditPaymentRequest::class, ['paymentRequest' => $pr])
            ->set('nominal', '55000000')
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $pr->fresh();
        $this->assertSame(55000000.0, (float) $fresh->nominal);
        $this->assertSame('draft', $fresh->status->value);
    }

    public function test_approved_can_be_marked_paid(): void
    {
        $user = User::factory()->admin()->create();
        $pr = $this->makePaymentRequest(['status' => 'approved']);

        Livewire::actingAs($user)
            ->test(IndexPaymentRequest::class)
            ->call('markPaid', $pr->id);

        $fresh = $pr->fresh();
        $this->assertSame('paid', $fresh->status->value);
        $this->assertNotNull($fresh->paid_at);

        $this->assertDatabaseHas('realisasi', [
            'sumber' => 'payment_request',
            'sumber_id' => $pr->id,
            'nominal' => $pr->nominal,
        ]);
    }

    public function test_approved_can_be_closed(): void
    {
        $user = User::factory()->admin()->create();
        $pr = $this->makePaymentRequest(['status' => 'approved']);

        Livewire::actingAs($user)
            ->test(IndexPaymentRequest::class)
            ->call('close', $pr->id);

        $this->assertSame('closed', $pr->fresh()->status->value);
    }

    public function test_draft_can_be_cancelled(): void
    {
        $user = User::factory()->create();
        $pr = $this->makePaymentRequest(['status' => 'draft', 'created_by' => $user->id]);

        Livewire::actingAs($user)
            ->test(IndexPaymentRequest::class)
            ->call('cancel', $pr->id);

        $this->assertSame('cancelled', $pr->fresh()->status->value);
    }

    public function test_staff_cannot_cancel_another_users_draft(): void
    {
        $owner = User::factory()->create();
        $staff = User::factory()->create();
        $pr = $this->makePaymentRequest(['status' => 'draft', 'created_by' => $owner->id]);

        Livewire::actingAs($staff)
            ->test(IndexPaymentRequest::class)
            ->call('cancel', $pr->id);

        $this->assertSame('draft', $pr->fresh()->status->value);
    }

    public function test_approved_payment_request_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $pr = $this->makePaymentRequest(['status' => 'approved']);

        Livewire::actingAs($user)
            ->test(IndexPaymentRequest::class)
            ->call('delete', $pr->id);

        $this->assertDatabaseHas('payment_requests', ['id' => $pr->id]);
    }

    public function test_number_generation_skips_existing_numbers(): void
    {
        $user = User::factory()->create();
        [$project, $akun] = $this->allocatedProjectAkun();
        $vendor = Vendor::factory()->create();

        PaymentRequest::factory()->create([
            'nomor' => 'PR-2026-001',
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'vendor_id' => $vendor->id,
            'supplier_id' => null,
        ]);

        Livewire::actingAs($user)
            ->test(CreatePaymentRequest::class)
            ->set('projectId', $project->id)
            ->set('akunId', $akun->id)
            ->set('vendorId', $vendor->id)
            ->set('tanggal', '2026-07-10')
            ->set('nominal', '60000000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('payment_requests', ['nomor' => 'PR-2026-002']);
    }

    public function test_account_item_list_only_shows_approved_allocations(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'draft']);

        Livewire::actingAs($user)
            ->test(CreatePaymentRequest::class)
            ->set('projectId', $project->id)
            ->assertSee('No approved account allocation for this project yet')
            ->assertDontSee($akun->kode_akun);
    }

    public function test_account_item_appears_for_approved_allocation(): void
    {
        $user = User::factory()->create();
        [$project, $akun] = $this->allocatedProjectAkun();

        Livewire::actingAs($user)
            ->test(CreatePaymentRequest::class)
            ->set('projectId', $project->id)
            ->assertSee($akun->kode_akun);
    }

    /**
     * Create a project with an approved allocation for one akun.
     *
     * @return array{0: Project, 1: Akun}
     */
    private function allocatedProjectAkun(): array
    {
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'approved']);

        return [$project, $akun];
    }

    /**
     * Create a payment request with the given status overrides.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function makePaymentRequest(array $overrides = []): PaymentRequest
    {
        [$project, $akun] = $this->allocatedProjectAkun();
        $vendor = Vendor::factory()->create();
        $supplier = Supplier::factory()->create();

        return PaymentRequest::factory()->create(array_merge([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'vendor_id' => $vendor->id,
            'supplier_id' => null,
        ], $overrides));
    }
}
