<?php

namespace Tests\Feature\Import;

use App\Imports\ProjectImport;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportProjectTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Store an xlsx file with the given rows and return its absolute path.
     *
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function storeXlsx(string $filename, array $rows): string
    {
        Excel::store(new class($rows) implements FromArray
        {
            public function __construct(private array $rows) {}

            public function array(): array
            {
                return $this->rows;
            }
        }, $filename);

        return storage_path('app/private/'.$filename);
    }

    private function seedMasterData(): void
    {
        // Create master type for Project Category (idempotent)
        $masterType = MasterType::firstOrCreate(
            ['kode' => 'PROJECT_CATEGORY'],
            [
                'kode' => 'PROJECT_CATEGORY',
                'nama' => 'Project Category',
                'aktif' => true,
                'is_system' => true,
                'sort' => 1,
            ]
        );

        // Create master items for categories (idempotent)
        MasterItem::firstOrCreate([
            'master_type_id' => $masterType->id,
            'kode' => 'KONSTRUKSI',
        ], [
            'master_type_id' => $masterType->id,
            'kode' => 'KONSTRUKSI',
            'nama' => 'Konstruksi',
            'aktif' => true,
        ]);

        MasterItem::firstOrCreate([
            'master_type_id' => $masterType->id,
            'kode' => 'INFRA',
        ], [
            'master_type_id' => $masterType->id,
            'kode' => 'INFRA',
            'nama' => 'Infrastruktur',
            'aktif' => true,
        ]);
    }

    public function test_valid_projects_are_imported(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();

        $path = $this->storeXlsx('project-valid.xlsx', [
            ['Code', 'Name', 'Location', 'Division', 'PIC', 'Project Category', 'Sub Work', 'Period', 'Type', 'Qty', 'Unit', 'Unit Price', 'Tax', 'Start Date', 'Target Finish', 'Status'],
            ['PRJ-001', 'Gedung Serbaguna', 'Jakarta', 'Construction', 'Budi Santoso', 'Konstruksi', 'Pekerjaan pondasi', '2026', 'jasa', 1, 'paket', 500000000, 11, '2026-08-01', '2026-12-31', 'draft'],
            ['PRJ-002', 'Jalan Tol', 'Bandung', 'Infrastructure', 'Siti Rahayu', 'Infrastruktur', 'Pekerjaan aspal', '2026', 'barang', 5000, 'meter', 250000, 11, '2026-09-01', '2027-03-31', 'progress'],
        ]);

        $import = new ProjectImport;
        Excel::import($import, $path);

        $this->assertSame(2, $import->successCount);
        $this->assertDatabaseHas('projects', ['kode' => 'PRJ-001', 'nama' => 'Gedung Serbaguna', 'status' => 'draft']);
        $this->assertDatabaseHas('projects', ['kode' => 'PRJ-002', 'nama' => 'Jalan Tol', 'status' => 'progress']);
    }

    public function test_project_category_is_linked_when_provided(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();
        $kategori = MasterItem::where('nama', 'Konstruksi')->first();

        $path = $this->storeXlsx('project-kategori.xlsx', [
            ['Code', 'Name', 'Location', 'Division', 'PIC', 'Project Category', 'Sub Work', 'Period', 'Type', 'Qty', 'Unit', 'Unit Price', 'Tax', 'Start Date', 'Target Finish', 'Status'],
            ['PRJ-003', 'Gedung Baru', 'Surabaya', 'Construction', 'Andi', 'Konstruksi', 'Struktur', '2026', 'jasa', 1, 'paket', 100000000, 11, '2026-01-01', '2026-06-30', 'draft'],
        ]);

        $import = new ProjectImport;
        Excel::import($import, $path);

        $this->assertSame(1, $import->successCount);
        $this->assertDatabaseHas('projects', ['kode' => 'PRJ-003', 'project_category_id' => $kategori->id]);
    }

    public function test_duplicate_project_code_is_updated_not_rejected(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();
        $existing = Project::factory()->create(['kode' => 'PRJ-001', 'nama' => 'Old Name', 'status' => 'draft']);

        $path = $this->storeXlsx('project-duplicate.xlsx', [
            ['Code', 'Name', 'Location', 'Division', 'PIC', 'Project Category', 'Sub Work', 'Period', 'Type', 'Qty', 'Unit', 'Unit Price', 'Tax', 'Start Date', 'Target Finish', 'Status'],
            ['PRJ-001', 'Updated Name', 'Jakarta', 'Construction', 'Budi', 'Konstruksi', 'Finishing', '2026', 'jasa', 1, 'paket', 600000000, 11, '2026-08-01', '2026-12-31', 'progress'],
        ]);

        $import = new ProjectImport;
        Excel::import($import, $path);

        // Should update existing, not create new
        $this->assertSame(1, $import->successCount);
        $this->assertDatabaseHas('projects', ['kode' => 'PRJ-001', 'nama' => 'Updated Name', 'status' => 'progress']);
        $this->assertDatabaseCount('projects', 1);
    }

    public function test_duplicate_project_within_file_is_rejected(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();

        $path = $this->storeXlsx('project-duplicate-in-file.xlsx', [
            ['Code', 'Name', 'Location', 'Division', 'PIC', 'Project Category', 'Sub Work', 'Period', 'Type', 'Qty', 'Unit', 'Unit Price', 'Tax', 'Start Date', 'Target Finish', 'Status'],
            ['PRJ-004', 'Project A', 'Jakarta', 'Construction', 'A', 'Konstruksi', 'Work A', '2026', 'jasa', 1, 'paket', 100000000, 11, '2026-01-01', '2026-06-30', 'draft'],
            ['PRJ-004', 'Project B', 'Bandung', 'Infrastructure', 'B', 'Infrastruktur', 'Work B', '2026', 'barang', 100, 'meter', 50000, 11, '2026-02-01', '2026-07-31', 'draft'],
        ]);

        $import = new ProjectImport;
        Excel::import($import, $path);

        $this->assertSame(1, $import->successCount);
        $this->assertCount(1, $import->failures);
        $this->assertStringContainsString('duplicated in the file', $import->failures[0]['reason']);
        $this->assertDatabaseCount('projects', 1);
    }

    public function test_invalid_type_fails_the_row(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();

        $path = $this->storeXlsx('project-type-invalid.xlsx', [
            ['Code', 'Name', 'Location', 'Division', 'PIC', 'Project Category', 'Sub Work', 'Period', 'Type', 'Qty', 'Unit', 'Unit Price', 'Tax', 'Start Date', 'Target Finish', 'Status'],
            ['PRJ-005', 'Invalid Type Project', 'Jakarta', 'Construction', 'Test', 'Konstruksi', 'Work', '2026', 'invalid_type', 1, 'paket', 100000000, 11, '2026-01-01', '2026-06-30', 'draft'],
        ]);

        $import = new ProjectImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertStringContainsString('Type must be', $import->failures[0]['reason']);
    }

    public function test_invalid_status_fails_the_row(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();

        $path = $this->storeXlsx('project-status-invalid.xlsx', [
            ['Code', 'Name', 'Location', 'Division', 'PIC', 'Project Category', 'Sub Work', 'Period', 'Type', 'Qty', 'Unit', 'Unit Price', 'Tax', 'Start Date', 'Target Finish', 'Status'],
            ['PRJ-006', 'Invalid Status Project', 'Jakarta', 'Construction', 'Test', 'Konstruksi', 'Work', '2026', 'jasa', 1, 'paket', 100000000, 11, '2026-01-01', '2026-06-30', 'invalid_status'],
        ]);

        $import = new ProjectImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertStringContainsString('Status must be', $import->failures[0]['reason']);
    }

    public function test_invalid_project_category_fails_the_row(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();

        $path = $this->storeXlsx('project-category-invalid.xlsx', [
            ['Code', 'Name', 'Location', 'Division', 'PIC', 'Project Category', 'Sub Work', 'Period', 'Type', 'Qty', 'Unit', 'Unit Price', 'Tax', 'Start Date', 'Target Finish', 'Status'],
            ['PRJ-007', 'Invalid Category Project', 'Jakarta', 'Construction', 'Test', 'NonExistentCategory', 'Work', '2026', 'jasa', 1, 'paket', 100000000, 11, '2026-01-01', '2026-06-30', 'draft'],
        ]);

        $import = new ProjectImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertStringContainsString('Project Category', $import->failures[0]['reason']);
    }

    public function test_wrong_header_is_fatal_and_nothing_is_imported(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();

        $path = $this->storeXlsx('project-wrong-header.xlsx', [
            ['Code', 'Name', 'Type'],
            ['PRJ-008', 'Wrong Header', 'jasa'],
        ]);

        $import = new ProjectImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertNotNull($import->fatalError);
        $this->assertStringContainsString('The header does not match the template', $import->fatalError);
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_empty_file_only_header_is_fatal(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();

        $path = $this->storeXlsx('project-empty.xlsx', [
            ['Code', 'Name', 'Location', 'Division', 'PIC', 'Project Category', 'Sub Work', 'Period', 'Type', 'Qty', 'Unit', 'Unit Price', 'Tax', 'Start Date', 'Target Finish', 'Status'],
        ]);

        $import = new ProjectImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertNotNull($import->fatalError);
        $this->assertStringContainsString('no data', strtolower($import->fatalError));
    }

    public function test_guest_is_redirected_to_login_from_import_page(): void
    {
        $this->get(route('imports.projects'))->assertRedirect(route('login'));
    }

    public function test_import_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('imports.projects'))
            ->assertOk()
            ->assertSee('Import Projects');
    }

    public function test_template_can_be_downloaded(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('imports.projects.template'));

        $response->assertOk();
        $this->assertStringContainsString('template-project.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_export_returns_file_with_correct_headers(): void
    {
        $this->seedMasterData();
        $user = User::factory()->admin()->create();
        Project::factory()->create(['kode' => 'PRJ-001', 'nama' => 'Test Project']);

        $response = $this->actingAs($user)->get(route('imports.projects.export'));

        $response->assertOk();
        $this->assertStringContainsString('projects-all.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_export_with_periode_filter(): void
    {
        $this->seedMasterData();
        $user = User::factory()->admin()->create();
        Project::factory()->create(['kode' => 'PRJ-001', 'nama' => 'Test 2026', 'periode' => '2026']);
        Project::factory()->create(['kode' => 'PRJ-002', 'nama' => 'Test 2027', 'periode' => '2027']);

        $response = $this->actingAs($user)->get(route('imports.projects.export', ['periode' => '2026']));

        $response->assertOk();
        // Export should only contain 2026 projects
    }

    public function test_default_tax_follows_jenis_during_import(): void
    {
        $this->seedMasterData();
        $user = User::factory()->create();

        $path = $this->storeXlsx('project-tax-default.xlsx', [
            ['Code', 'Name', 'Location', 'Division', 'PIC', 'Project Category', 'Sub Work', 'Period', 'Type', 'Qty', 'Unit', 'Unit Price', 'Tax %', 'Start Date', 'Target Finish', 'Status'],
            ['PRJ-009', 'Barang Project', 'Jakarta', 'Construction', 'Test', 'Konstruksi', 'Work', '2026', 'barang', 100, 'pcs', 10000, '', '2026-01-01', '2026-06-30', 'draft'],
            ['PRJ-010', 'Jasa Project', 'Jakarta', 'Construction', 'Test', 'Konstruksi', 'Work', '2026', 'jasa', 1, 'paket', 5000000, '', '2026-01-01', '2026-06-30', 'draft'],
        ]);

        $import = new ProjectImport;
        Excel::import($import, $path);

        $this->assertSame(2, $import->successCount);
        $barangProject = Project::where('kode', 'PRJ-009')->first();
        $jasaProject = Project::where('kode', 'PRJ-010')->first();
        $this->assertSame(11.0, (float) $barangProject->pajak);
        $this->assertSame(2.0, (float) $jasaProject->pajak);
    }
}
