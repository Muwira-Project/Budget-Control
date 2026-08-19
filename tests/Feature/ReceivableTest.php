<?php

namespace Tests\Feature;

use App\Livewire\Receivables\Create as CreateReceivable;
use App\Livewire\Receivables\Index as IndexReceivable;
use App\Models\Project;
use App\Models\Receivable;
use App\Models\User;
use App\Services\ReceivableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ReceivableTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('receivables.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        Receivable::factory()->create();

        $this->actingAs($user)
            ->get(route('receivables.index'))
            ->assertOk();
    }

    public function test_receivable_is_auto_created_when_project_completed(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'status' => 'done',
            'qty' => 1,
            'harga_satuan' => 500000000,
            'pajak' => 2,
        ]);

        $this->assertDatabaseHas('receivables', ['project_id' => $project->id]);

        $receivable = Receivable::where('project_id', $project->id)->first();
        $this->assertSame(510000000.0, (float) $receivable->nominal);
    }

    public function test_receivable_is_not_created_for_active_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['status' => 'progress']);

        $this->assertDatabaseMissing('receivables', ['project_id' => $project->id]);
    }

    public function test_receivable_can_be_created_manually(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['status' => 'progress']);

        Livewire::actingAs($user)
            ->test(CreateReceivable::class)
            ->set('projectId', $project->id)
            ->set('tanggal', '2026-07-01')
            ->set('nominal', '250000000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('receivables.index'));

        $this->assertDatabaseHas('receivables', ['project_id' => $project->id, 'nominal' => 250000000]);
    }

    public function test_receivable_cannot_duplicate_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['status' => 'progress']);
        Receivable::factory()->create(['project_id' => $project->id]);

        Livewire::actingAs($user)
            ->test(CreateReceivable::class)
            ->set('projectId', $project->id)
            ->set('tanggal', '2026-07-01')
            ->set('nominal', '250000000')
            ->call('save')
            ->assertHasErrors(['project_id']);
    }

    public function test_receivable_status_tracks_payments(): void
    {
        $user = User::factory()->create();
        $receivable = Receivable::factory()->create(['nominal' => 100000000, 'nominal_dibayar' => 0]);

        $this->assertSame('belum_dibayar', $receivable->status->value);

        $receivable->update(['nominal_dibayar' => 50000000]);
        $this->assertSame('sebagian', $receivable->status->value);

        $receivable->update(['nominal_dibayar' => 100000000]);
        $this->assertSame('lunas', $receivable->status->value);
    }

    public function test_receivable_can_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $receivable = Receivable::factory()->create();

        Livewire::actingAs($admin)
            ->test(IndexReceivable::class)
            ->call('delete', $receivable->id);

        $this->assertSoftDeleted('receivables', ['id' => $receivable->id]);
    }

    public function test_receivable_nominal_cannot_be_reduced_below_paid_amount(): void
    {
        $receivable = Receivable::factory()->create(['nominal' => 100000000, 'nominal_dibayar' => 80000000]);

        $this->expectException(ValidationException::class);

        app(ReceivableService::class)->update($receivable, [
            'project_id' => $receivable->project_id,
            'tanggal' => $receivable->tanggal->format('Y-m-d'),
            'nominal' => 50000000,
            'keterangan' => null,
        ]);
    }

    public function test_receivable_rejects_duplicate_invoice_number(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        Receivable::factory()->create(['nomor_invoice' => 'INV-2026-100']);

        Livewire::actingAs($user)
            ->test(CreateReceivable::class)
            ->set('projectId', $project->id)
            ->set('tanggal', '2026-07-01')
            ->set('nomorInvoice', 'inv-2026-100')
            ->set('nominal', '100000000')
            ->call('save')
            ->assertHasErrors(['nomor_invoice']);
    }

    public function test_receivable_aging_filter_over_90(): void
    {
        $user = User::factory()->create();
        $old = Receivable::factory()->create(['jatuh_tempo' => now()->subDays(120), 'nominal' => 100000, 'nominal_dibayar' => 0]);
        $recent = Receivable::factory()->create(['jatuh_tempo' => now()->subDays(10), 'nominal' => 100000, 'nominal_dibayar' => 0]);

        $component = Livewire::actingAs($user)
            ->test(IndexReceivable::class)
            ->set('agingFilter', 'over_90');

        $ids = collect($component->instance()->receivables->items())->pluck('id');
        $this->assertTrue($ids->contains($old->id));
        $this->assertFalse($ids->contains($recent->id));
    }
}
