<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Payable;
use App\Models\PaymentRequest;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\User;
use App\Models\Vendor;
use App\Services\PaymentRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrApSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_payment_request_creates_payable(): void
    {
        $admin = User::factory()->admin()->create();
        $pr = $this->makePaymentRequest(['status' => 'waiting']);

        app(PaymentRequestService::class)->approve($pr);

        $payable = Payable::where('payment_request_id', $pr->id)->first();
        $this->assertNotNull($payable);
        $this->assertSame($pr->project_id, $payable->project_id);
        $this->assertSame($pr->akun_id, $payable->akun_id);
        $this->assertSame($pr->vendor_id, $payable->vendor_id);
        $this->assertSame((float) $pr->nominal, (float) $payable->nominal);
    }

    public function test_approving_again_does_not_duplicate_payable(): void
    {
        $admin = User::factory()->admin()->create();
        $pr = $this->makePaymentRequest(['status' => 'waiting']);

        app(PaymentRequestService::class)->approve($pr);
        app(PaymentRequestService::class)->approve($pr);

        $this->assertSame(1, Payable::where('payment_request_id', $pr->id)->count());
    }

    public function test_rejecting_payment_request_removes_payable(): void
    {
        $admin = User::factory()->admin()->create();
        $pr = $this->makePaymentRequest(['status' => 'waiting']);

        app(PaymentRequestService::class)->approve($pr);
        $this->assertSame(1, Payable::where('payment_request_id', $pr->id)->count());

        app(PaymentRequestService::class)->reject($pr);

        $this->assertSame(0, Payable::where('payment_request_id', $pr->id)->count());
    }

    public function test_marking_paid_sets_payable_as_paid(): void
    {
        $admin = User::factory()->admin()->create();
        $pr = $this->makePaymentRequest(['status' => 'approved', 'nominal' => 60000000]);

        app(PaymentRequestService::class)->markPaid($pr);

        $payable = Payable::where('payment_request_id', $pr->id)->first();
        $this->assertNotNull($payable);
        $this->assertSame(60000000.0, (float) $payable->nominal_dibayar);
    }

    public function test_deleting_payment_request_removes_payable(): void
    {
        $admin = User::factory()->admin()->create();
        $pr = $this->makePaymentRequest(['status' => 'approved']);

        app(PaymentRequestService::class)->approve($pr);
        $this->assertSame(1, Payable::where('payment_request_id', $pr->id)->count());

        app(PaymentRequestService::class)->delete($pr);

        $this->assertSame(0, Payable::where('payment_request_id', $pr->id)->count());
    }

    public function test_payment_request_number_is_not_reused_after_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'approved']);
        $vendor = Vendor::factory()->create();
        $data = [
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'vendor_id' => $vendor->id,
            'tanggal' => '2026-08-01',
            'nominal' => 50000000,
            'prioritas' => 'medium',
        ];

        $first = app(PaymentRequestService::class)->create($data);
        app(PaymentRequestService::class)->delete($first);

        $second = app(PaymentRequestService::class)->create($data);

        $this->assertNotSame($first->nomor, $second->nomor);
        $this->assertMatchesRegularExpression('/^PR-\d{4}-\d{3}$/', $second->nomor);
    }

    private function makePaymentRequest(array $overrides = []): PaymentRequest
    {
        $project = Project::factory()->create();
        $akun = Akun::factory()->create();
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'approved']);
        $vendor = Vendor::factory()->create();

        return PaymentRequest::factory()->create(array_merge([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'vendor_id' => $vendor->id,
            'supplier_id' => null,
        ], $overrides));
    }
}
