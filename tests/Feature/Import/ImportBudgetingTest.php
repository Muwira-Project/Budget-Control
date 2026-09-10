<?php

namespace Tests\Feature\Import;

use App\Imports\BudgetingImport;
use App\Models\Akun;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportBudgetingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Store an xlsx file with the given rows and return its absolute path.
     *
     * @param array<int, array<int, mixed>> $rows
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

    public function test_project_and_non_project_budgeting_are_imported_successfully(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create(['kode' => 'PRJ-001', 'nama' => 'Pembangunan Gedung A']);
        $akunBiaya = Akun::factory()->create(['kode_akun' => '5-100', 'nama_akun' => 'Biaya Material', 'jenis_akun' => 'pengeluaran']);
        $akunListrik = Akun::factory()->create(['kode_akun' => '6-100', 'nama_akun' => 'Biaya Listrik', 'jenis_akun' => 'pengeluaran']);
        $akunBunga = Akun::factory()->create(['kode_akun' => '4-200', 'nama_akun' => 'Pendapatan Bunga', 'jenis_akun' => 'pendapatan']);
        $masterType = MasterType::whereRaw('LOWER(TRIM(kode)) = ?', ['supplier'])->first() ?? MasterType::factory()->create(['kode' => 'supplier', 'nama' => 'Supplier']);

        $path = $this->storeXlsx('budgeting-valid.xlsx', [
            ['Project Code', 'Account Code', 'Type', 'Party Type', 'Party Name', 'Budget', 'Allocation', 'Status'],
            ['PRJ-001', '5-100', 'project', '', '', 50000000, 50000000, 'approved'],
            ['', '5-100', 'ap', 'supplier', 'PT Semen Jaya', 25000000, 25000000, 'approved'],
            ['', '6-100', 'other_outcome', '', 'Listrik Kantor', 5000000, 5000000, 'approved'],
            ['', '4-200', 'other_income', '', 'Bunga Bank Mandiri', 1500000, 1500000, 'approved'],
        ]);

        $import = new BudgetingImport;
        Excel::import($import, $path);

        $this->assertSame(4, $import->successCount, json_encode($import->failures));
        $this->assertEmpty($import->failures, json_encode($import->failures));

        // Assert Project Budget
        $this->assertDatabaseHas('project_akuns', [
            'project_id' => $project->id,
            'akun_id' => $akunBiaya->id,
            'budget' => 50000000,
            'allocation' => 50000000,
            'status' => 'approved',
        ]);

        // Assert Non-Project AP
        $this->assertDatabaseHas('project_akuns', [
            'project_id' => null,
            'akun_id' => $akunBiaya->id,
            'type' => 'ap',
            'pihak_type_id' => $masterType->id,
            'budget' => 25000000,
            'allocation' => 25000000,
        ]);

        // Assert Non-Project Other Outcome
        $this->assertDatabaseHas('project_akuns', [
            'project_id' => null,
            'akun_id' => $akunListrik->id,
            'type' => 'other_outcome',
            'custom_name' => 'Listrik Kantor',
            'budget' => 5000000,
            'allocation' => 5000000,
        ]);

        // Assert Non-Project Other Income
        $this->assertDatabaseHas('project_akuns', [
            'project_id' => null,
            'akun_id' => $akunBunga->id,
            'type' => 'other_income',
            'custom_name' => 'Bunga Bank Mandiri',
            'budget' => 1500000,
            'allocation' => 1500000,
        ]);
    }

    public function test_reimport_updates_existing_project_akun_budget_and_allocation(): void
    {
        $project = Project::factory()->create(['kode' => 'PRJ-002']);
        $akun = Akun::factory()->create(['kode_akun' => '5-200']);

        ProjectAkun::create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'type' => 'other_outcome',
            'budget' => 10000000,
            'allocation' => 10000000,
            'status' => 'draft',
        ]);

        $path = $this->storeXlsx('budgeting-update.xlsx', [
            ['Project Code', 'Account Code', 'Type', 'Party Type', 'Party Name', 'Budget', 'Allocation', 'Status'],
            ['PRJ-002', '5-200', '', '', '', 20000000, 20000000, 'approved'],
        ]);

        $import = new BudgetingImport;
        Excel::import($import, $path);

        $this->assertSame(1, $import->successCount, json_encode($import->failures).' fatal: '.$import->fatalError);
        $this->assertDatabaseHas('project_akuns', [
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'budget' => 20000000,
            'allocation' => 20000000,
            'status' => 'approved',
        ]);
    }

    public function test_missing_required_fields_fails_row(): void
    {
        $path = $this->storeXlsx('budgeting-missing.xlsx', [
            ['Project Code', 'Account Code', 'Type', 'Party Type', 'Party Name', 'Budget', 'Allocation', 'Status'],
            ['PRJ-001', '', '', '', '', 1000000, 1000000, 'approved'],
            ['PRJ-999', '5-999', '', '', '', 1000000, 1000000, 'approved'],
        ]);

        $import = new BudgetingImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertCount(2, $import->failures);
    }

    public function test_wrong_header_is_fatal_in_budgeting_import(): void
    {
        $path = $this->storeXlsx('budgeting-wrong-header.xlsx', [
            ['Project', 'Account', 'Amount'],
            ['PRJ-001', '5-100', 1000000],
        ]);

        $import = new BudgetingImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertNotNull($import->fatalError);
        $this->assertStringContainsString('The header does not match the template', $import->fatalError);
    }

    public function test_budgeting_import_page_renders_and_template_downloads(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('imports.budgeting'))
            ->assertOk()
            ->assertSee('Import Budgeting');

        $response = $this->actingAs($admin)->get(route('imports.budgeting.template'));
        $response->assertOk();
        $this->assertStringContainsString('template-budgeting.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_modern_template_with_11_columns_and_non_project_keywords(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create(['kode' => 'PRJ-101', 'nama' => 'Proyek Gedung B']);
        $akunBiaya = Akun::factory()->create(['kode_akun' => '5-101', 'nama_akun' => 'Biaya Material']);
        $akunListrik = Akun::factory()->create(['kode_akun' => '6-101', 'nama_akun' => 'Biaya Listrik']);
        $akunBunga = Akun::factory()->create(['kode_akun' => '4-201', 'nama_akun' => 'Pendapatan Bunga']);

        $path = $this->storeXlsx('budgeting-modern.xlsx', [
            ['Project Code', 'Project Name', 'Budgeting Number', 'Account Code', 'Account Name', 'Type', 'Party Type', 'Party Name', 'Budget', 'Allocation', 'Status'],
            ['PRJ-101', 'Proyek Gedung B', 'BP-001', '5-101', 'Biaya Material', 'other_outcome', '', '', 40000000, 40000000, 'approved'],
            ['Non-Project', '', '', '6-101', 'Biaya Listrik', 'other_outcome', '', 'Listrik Kantor Pusat', 3000000, 3000000, 'approved'],
            ['Non Project', '', '', '4-201', 'Pendapatan Bunga', 'other_income', '', 'Bunga Deposito', 1000000, 1000000, 'approved'],
            ['non-proyek', '', '', '5-101', 'Biaya Material', 'other_outcome', '', 'Material Operasional', 2000000, 2000000, 'approved'],
        ]);

        $import = new BudgetingImport;
        Excel::import($import, $path);

        $this->assertSame(4, $import->successCount, json_encode($import->failures).' fatal: '.$import->fatalError);
        $this->assertEmpty($import->failures);

        // Verify project row
        $this->assertDatabaseHas('project_akuns', [
            'project_id' => $project->id,
            'akun_id' => $akunBiaya->id,
            'budget' => 40000000,
        ]);

        // Verify all 3 non-project rows exist in DB with project_id = null
        $this->assertSame(3, ProjectAkun::whereNull('project_id')->count());
        $this->assertDatabaseHas('project_akuns', [
            'project_id' => null,
            'akun_id' => $akunListrik->id,
            'custom_name' => 'Listrik Kantor Pusat',
        ]);
        $this->assertDatabaseHas('project_akuns', [
            'project_id' => null,
            'akun_id' => $akunBunga->id,
            'custom_name' => 'Bunga Deposito',
        ]);
        $this->assertDatabaseHas('project_akuns', [
            'project_id' => null,
            'akun_id' => $akunBiaya->id,
            'custom_name' => 'Material Operasional',
        ]);
    }

    public function test_non_project_allocations_appear_in_budgeting_index_and_can_be_filtered(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create(['kode' => 'PRJ-202']);
        $akun = Akun::factory()->create(['kode_akun' => '6-202', 'nama_akun' => 'Internet Kantor']);

        $projectAlloc = ProjectAkun::create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'type' => 'other_outcome',
            'budget' => 10000000,
            'allocation' => 10000000,
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        $nonProjectAlloc = ProjectAkun::create([
            'project_id' => null,
            'akun_id' => $akun->id,
            'type' => 'other_outcome',
            'custom_name' => 'Internet Wi-Fi Kantor',
            'budget' => 2500000,
            'allocation' => 2500000,
            'status' => 'approved',
            'created_by' => $admin->id,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Budgeting\Index::class)
            ->assertSee('Internet Wi-Fi Kantor')
            ->assertSee('Non-Project')
            ->set('projectId', 'non-project')
            ->assertSee('Internet Wi-Fi Kantor')
            ->assertSet('projectId', 'non-project')
            ->set('search', 'Wi-Fi')
            ->assertSee('Internet Wi-Fi Kantor');
    }
}
