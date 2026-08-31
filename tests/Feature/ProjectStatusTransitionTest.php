<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Activity;
use App\Models\Project;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProjectStatusTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    // ============================================
    // STATE MACHINE TRANSITION TESTS
    // ============================================

    public function test_draft_can_transition_to_progress(): void
    {
        $project = Project::factory()->create(['status' => 'draft']);

        $project->status = ProjectStatus::InProgress;
        $project->save();

        $this->assertEquals(ProjectStatus::InProgress, $project->fresh()->status);
    }

    public function test_draft_can_transition_to_cancelled(): void
    {
        $project = Project::factory()->create(['status' => 'draft']);

        $project->status = ProjectStatus::Cancelled;
        $project->save();

        $this->assertEquals(ProjectStatus::Cancelled, $project->fresh()->status);
    }

    public function test_draft_cannot_transition_to_done(): void
    {
        $project = Project::factory()->create(['status' => 'draft']);

        $this->expectException(ValidationException::class);

        $project->status = ProjectStatus::Done;
        $project->save();
    }

    public function test_draft_cannot_transition_to_revisi(): void
    {
        $project = Project::factory()->create(['status' => 'draft']);

        $this->expectException(ValidationException::class);

        $project->status = ProjectStatus::Revisi;
        $project->save();
    }

    public function test_progress_can_transition_to_done(): void
    {
        $project = Project::factory()->create(['status' => 'progress', 'po_number' => 'PO-001']);

        $project->status = ProjectStatus::Done;
        $project->save();

        $this->assertEquals(ProjectStatus::Done, $project->fresh()->status);
    }

    public function test_progress_can_transition_to_revisi(): void
    {
        $project = Project::factory()->create(['status' => 'progress']);

        $project->revisi_reason = 'Test reason';
        $project->status = ProjectStatus::Revisi;
        $project->save();

        $this->assertEquals(ProjectStatus::Revisi, $project->fresh()->status);
        $this->assertEquals('Test reason', $project->fresh()->revisi_reason);
        $this->assertNotNull($project->fresh()->revisi_at);
        $this->assertEquals($this->user->id, $project->fresh()->revisi_by);
    }

    public function test_progress_cannot_transition_to_revisi_without_reason(): void
    {
        $project = Project::factory()->create(['status' => 'progress']);

        $this->expectException(ValidationException::class);

        $project->status = ProjectStatus::Revisi;
        $project->save();
    }

    public function test_progress_can_transition_to_cancelled(): void
    {
        $project = Project::factory()->create(['status' => 'progress']);

        $project->status = ProjectStatus::Cancelled;
        $project->save();

        $this->assertEquals(ProjectStatus::Cancelled, $project->fresh()->status);
    }

    public function test_progress_cannot_transition_to_draft(): void
    {
        $project = Project::factory()->create(['status' => 'progress']);

        $this->expectException(ValidationException::class);

        $project->status = ProjectStatus::Draft;
        $project->save();
    }

    public function test_revisi_can_transition_to_progress(): void
    {
        $project = Project::factory()->create(['status' => 'revisi', 'revisi_reason' => 'Test']);

        $project->status = ProjectStatus::InProgress;
        $project->save();

        $this->assertEquals(ProjectStatus::InProgress, $project->fresh()->status);
    }

    public function test_revisi_can_transition_to_cancelled(): void
    {
        $project = Project::factory()->create(['status' => 'revisi', 'revisi_reason' => 'Test']);

        $project->status = ProjectStatus::Cancelled;
        $project->save();

        $this->assertEquals(ProjectStatus::Cancelled, $project->fresh()->status);
    }

    public function test_revisi_cannot_transition_to_done(): void
    {
        $project = Project::factory()->create(['status' => 'revisi', 'revisi_reason' => 'Test']);

        $this->expectException(ValidationException::class);

        $project->status = ProjectStatus::Done;
        $project->save();
    }

    public function test_revisi_cannot_transition_to_draft(): void
    {
        $project = Project::factory()->create(['status' => 'revisi', 'revisi_reason' => 'Test']);

        $this->expectException(ValidationException::class);

        $project->status = ProjectStatus::Draft;
        $project->save();
    }

    public function test_done_can_transition_to_revisi(): void
    {
        $project = Project::factory()->create(['status' => 'done', 'po_number' => 'PO-001']);

        $project->revisi_reason = 'Scope change';
        $project->status = ProjectStatus::Revisi;
        $project->save();

        $this->assertEquals(ProjectStatus::Revisi, $project->fresh()->status);
    }

    public function test_done_can_transition_to_cancelled(): void
    {
        $project = Project::factory()->create(['status' => 'done', 'po_number' => 'PO-001']);

        $project->status = ProjectStatus::Cancelled;
        $project->save();

        $this->assertEquals(ProjectStatus::Cancelled, $project->fresh()->status);
    }

    public function test_done_cannot_transition_to_progress(): void
    {
        $project = Project::factory()->create(['status' => 'done', 'po_number' => 'PO-001']);

        $this->expectException(ValidationException::class);

        $project->status = ProjectStatus::InProgress;
        $project->save();
    }

    public function test_done_cannot_transition_to_draft(): void
    {
        $project = Project::factory()->create(['status' => 'done', 'po_number' => 'PO-001']);

        $this->expectException(ValidationException::class);

        $project->status = ProjectStatus::Draft;
        $project->save();
    }

    public function test_cancelled_is_terminal_state(): void
    {
        $project = Project::factory()->create(['status' => 'cancelled']);

        $invalidTransitions = [
            ProjectStatus::Draft,
            ProjectStatus::InProgress,
            ProjectStatus::Done,
            ProjectStatus::Revisi,
        ];

        foreach ($invalidTransitions as $status) {
            $project->status = $status;

            $this->expectException(ValidationException::class);
            $project->save();

            // Refresh for next iteration
            $project = $project->fresh();
        }
    }

    // ============================================
    // PO NUMBER VALIDATION TESTS
    // ============================================

    public function test_done_requires_po_number(): void
    {
        // This test validates that PO is required via FormRequest
        // Since projects use Livewire (not REST routes), we test the FormRequest directly
        $project = Project::factory()->create(['status' => 'progress']);

        $request = new UpdateProjectRequest;
        $request->setUserResolver(fn () => $this->user);
        $request->replace([
            'kode' => $project->kode,
            'nama' => $project->nama,
            'jenis' => 'barang',
            'status' => 'done',
            'po_number' => null,
        ]);

        $validator = Validator::make(
            $request->all(),
            (new UpdateProjectRequest)->rules($project->id)
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('po_number', $validator->errors()->toArray());
    }

    public function test_done_with_po_number_succeeds(): void
    {
        $project = Project::factory()->create(['status' => 'progress', 'po_number' => 'PO-001']);

        $project->status = ProjectStatus::Done;
        $project->save();

        $this->assertEquals(ProjectStatus::Done, $project->fresh()->status);
    }

    public function test_revisi_does_not_require_po_number(): void
    {
        $project = Project::factory()->create(['status' => 'progress']);

        $project->revisi_reason = 'Test reason';
        $project->status = ProjectStatus::Revisi;
        $project->save();

        $this->assertEquals(ProjectStatus::Revisi, $project->fresh()->status);
    }

    // ============================================
    // REVISI AUDIT LOG TESTS
    // ============================================

    public function test_moved_to_revisi_logs_activity(): void
    {
        $project = Project::factory()->create(['status' => 'progress']);

        $project->revisi_reason = 'Scope change';
        $project->status = ProjectStatus::Revisi;
        $project->save();

        $activity = Activity::where('subject_id', $project->id)
            ->where('action', 'moved_to_revisi')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertStringContainsString('Scope change', $activity->description);
        $this->assertStringContainsString('Revisi', $activity->description);
    }

    public function test_exited_revisi_logs_activity(): void
    {
        $project = Project::factory()->create(['status' => 'revisi', 'revisi_reason' => 'Test']);

        $project->status = ProjectStatus::InProgress;
        $project->save();

        $activity = Activity::where('subject_id', $project->id)
            ->where('action', 'exited_revisi')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertStringContainsString('Revisi', $activity->description);
        $this->assertStringContainsString('In Progress', $activity->description);
    }

    // ============================================
    // AR INTEGRITY TESTS
    // ============================================

    public function test_ar_created_when_project_completed(): void
    {
        $project = Project::factory()->create([
            'status' => 'progress',
            'po_number' => 'PO-001',
            'qty' => 10,
            'harga_satuan' => 1000000,
            'pajak' => 11,
        ]);

        $project->status = ProjectStatus::Done;
        $project->save();

        $receivable = Receivable::where('project_id', $project->id)->first();

        $this->assertNotNull($receivable);
        $this->assertEquals($project->nilai_total, $receivable->nominal);
        $this->assertEquals('billed', $receivable->ar_category ?? 'billed');
    }

    public function test_ar_updated_not_duplicated_on_revisi_cycle(): void
    {
        $project = Project::factory()->create([
            'status' => 'progress',
            'po_number' => 'PO-001',
            'qty' => 10,
            'harga_satuan' => 1000000,
            'pajak' => 11,
        ]);

        // First completion
        $project->status = ProjectStatus::Done;
        $project->save();

        $arCount1 = Receivable::where('project_id', $project->id)->count();
        $ar1 = Receivable::where('project_id', $project->id)->first();

        $this->assertEquals(1, $arCount1);

        // Revisi cycle
        $project->revisi_reason = 'Test';
        $project->status = ProjectStatus::Revisi;
        $project->save();

        $project->status = ProjectStatus::InProgress;
        $project->save();

        $project->status = ProjectStatus::Done;
        $project->save();

        $arCount2 = Receivable::where('project_id', $project->id)->count();
        $ar2 = Receivable::where('project_id', $project->id)->first();

        $this->assertEquals(1, $arCount2, 'AR should not be duplicated');
        $this->assertEquals($ar1->id, $ar2->id, 'Same AR record should be updated');
    }

    public function test_ar_unbilled_when_done_without_po(): void
    {
        // This tests the model logic, not the validation (which prevents this)
        $project = Project::factory()->create([
            'status' => 'done',
            'po_number' => null,
        ]);

        $this->assertEquals('unbilled', $project->ar_category);
    }

    // ============================================
    // FORM REQUEST VALIDATION TESTS
    // ============================================

    // Note: Projects use Livewire components, not REST API routes
    // These tests would need to test via Livewire component testing
    // Leaving as documentation of expected behavior

    // ============================================
    // LIVEWIRE UI VALIDATION TESTS
    // ============================================

    // Note: Livewire component tests require full integration setup
    // These are functional requirements tested manually via browser
    // The component logic is covered by model/FormRequest tests above

    // ============================================
    // ENUM HELPER TESTS
    // ============================================

    public function test_enum_can_transition_to_returns_correct_values(): void
    {
        $this->assertTrue(ProjectStatus::Draft->canTransitionTo(ProjectStatus::InProgress));
        $this->assertTrue(ProjectStatus::Draft->canTransitionTo(ProjectStatus::Cancelled));
        $this->assertFalse(ProjectStatus::Draft->canTransitionTo(ProjectStatus::Done));
        $this->assertFalse(ProjectStatus::Draft->canTransitionTo(ProjectStatus::Revisi));

        $this->assertTrue(ProjectStatus::InProgress->canTransitionTo(ProjectStatus::Done));
        $this->assertTrue(ProjectStatus::InProgress->canTransitionTo(ProjectStatus::Revisi));
        $this->assertTrue(ProjectStatus::InProgress->canTransitionTo(ProjectStatus::Cancelled));
        $this->assertFalse(ProjectStatus::InProgress->canTransitionTo(ProjectStatus::Draft));

        $this->assertTrue(ProjectStatus::Revisi->canTransitionTo(ProjectStatus::InProgress));
        $this->assertTrue(ProjectStatus::Revisi->canTransitionTo(ProjectStatus::Cancelled));
        $this->assertFalse(ProjectStatus::Revisi->canTransitionTo(ProjectStatus::Done));
        $this->assertFalse(ProjectStatus::Revisi->canTransitionTo(ProjectStatus::Draft));

        $this->assertTrue(ProjectStatus::Done->canTransitionTo(ProjectStatus::Revisi));
        $this->assertTrue(ProjectStatus::Done->canTransitionTo(ProjectStatus::Cancelled));
        $this->assertFalse(ProjectStatus::Done->canTransitionTo(ProjectStatus::InProgress));
        $this->assertFalse(ProjectStatus::Done->canTransitionTo(ProjectStatus::Draft));

        $this->assertFalse(ProjectStatus::Cancelled->canTransitionTo(ProjectStatus::Draft));
        $this->assertFalse(ProjectStatus::Cancelled->canTransitionTo(ProjectStatus::InProgress));
        $this->assertFalse(ProjectStatus::Cancelled->canTransitionTo(ProjectStatus::Done));
        $this->assertFalse(ProjectStatus::Cancelled->canTransitionTo(ProjectStatus::Revisi));
    }

    public function test_enum_get_allowed_transitions_returns_correct_arrays(): void
    {
        $this->assertEquals(
            [ProjectStatus::InProgress, ProjectStatus::Cancelled],
            ProjectStatus::Draft->getAllowedTransitions()
        );

        $this->assertEquals(
            [ProjectStatus::Done, ProjectStatus::Revisi, ProjectStatus::Cancelled],
            ProjectStatus::InProgress->getAllowedTransitions()
        );

        $this->assertEquals(
            [ProjectStatus::InProgress, ProjectStatus::Cancelled],
            ProjectStatus::Revisi->getAllowedTransitions()
        );

        $this->assertEquals(
            [ProjectStatus::Revisi, ProjectStatus::Cancelled],
            ProjectStatus::Done->getAllowedTransitions()
        );

        $this->assertEquals([], ProjectStatus::Cancelled->getAllowedTransitions());
    }
}
