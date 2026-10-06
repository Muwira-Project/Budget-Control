<?php

namespace Tests\Feature\Import;

use App\Exports\PayableTemplateExport;
use App\Imports\PayableImport;
use App\Livewire\Imports\ImportPayables;
use App\Models\Akun;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Payable;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportPayableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create master types (match migration codes: uppercase)
        $this->vendorType = MasterType::firstOrCreate(
            ['kode' => 'VENDOR'],
            ['nama' => 'Vendor', 'flag_ar' => true, 'flag_ap' => true, 'aktif' => true, 'is_system' => true]
        );

        $this->supplierType = MasterType::firstOrCreate(
            ['kode' => 'SUPPLIER'],
            ['nama' => 'Supplier', 'flag_ar' => true, 'flag_ap' => true, 'aktif' => true, 'is_system' => true]
        );

        // Create master items
        $this->vendor = MasterItem::factory()->create(['master_type_id' => $this->vendorType->id, 'nama' => 'PT Vendor Utama', 'flag_ap' => true]);
        $this->supplier = MasterItem::factory()->create(['master_type_id' => $this->supplierType->id, 'nama' => 'Supplier B', 'flag_ap' => true]);

        // Create project
        $this->project = Project::factory()->create(['kode' => 'PRJ-001', 'nama' => 'Project Test']);
        $this->akun = Akun::factory()->create(['kode_akun' => 'AKUN-001']);
        ProjectAkun::create(['project_id' => $this->project->id, 'akun_id' => $this->akun->id, 'budget' => 100000000, 'allocation' => 100000000, 'status' => 'approved']);

        $this->user = User::factory()->create();
    }

    /** @test */
    public function test_import_with_project_code(): void
    {
        $path = $this->storeXlsx('payable-with-project.xlsx', [
            ['Project Code', 'Akun Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['PRJ-001', 'AKUN-001', 'VENDOR', 'PT Vendor Utama', '2026-08-15', 'INV-2026-001', '2026-09-14', 50000000, 0, 'Pembayaran pertama'],
        ]);

        $import = new PayableImport(true);
        Excel::import($import, $path);

        $this->assertEquals(1, $import->successCount);
        $this->assertEmpty($import->failures);
        $this->assertNull($import->fatalError);

        $this->assertDatabaseHas('payables', [
            'project_id' => $this->project->id,
            'akun_id' => $this->akun->id,
            'nomor_invoice' => 'INV-2026-001',
            'nominal' => 50000000,
        ]);
    }

    /** @test */
    public function test_import_without_project_code(): void
    {
        $path = $this->storeXlsx('payable-without-project.xlsx', [
            ['Akun Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['AKUN-001', 'VENDOR', 'PT Vendor Utama', '2026-08-15', 'INV-2026-002', '2026-09-14', 50000000, 0, 'Pembayaran pertama'],
        ]);

        $import = new PayableImport(false);
        Excel::import($import, $path);

        $this->assertEquals(1, $import->successCount);
        $this->assertEmpty($import->failures);
        $this->assertNull($import->fatalError);

        $this->assertDatabaseHas('payables', [
            'project_id' => null,
            'akun_id' => $this->akun->id,
            'nomor_invoice' => 'INV-2026-002',
            'nominal' => 50000000,
        ]);
    }

    /** @test */
    public function test_import_without_project_code_duplicate_invoice_fails(): void
    {
        // Create existing payable with same invoice (global unique)
        Payable::factory()->create([
            'nomor_invoice' => 'INV-EXISTING',
            'project_id' => null,
            'akun_id' => $this->akun->id,
            'pihak_type_id' => $this->vendorType->id,
            'pihak_item_id' => $this->vendor->id,
            'tanggal' => '2026-08-01',
            'nominal' => 10000000,
        ]);

        $path = $this->storeXlsx('payable-duplicate.xlsx', [
            ['Akun Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['AKUN-001', 'VENDOR', 'PT Vendor Utama', '2026-08-15', 'INV-EXISTING', '2026-09-14', 50000000, 0, 'Duplicate'],
        ]);

        $import = new PayableImport(false);
        Excel::import($import, $path);

        $this->assertEquals(0, $import->successCount);
        $this->assertCount(1, $import->failures);
        $this->assertStringContainsString('global unique', $import->failures[0]['reason']);
    }

    public function test_import_with_project_code_rejects_invoice_used_by_another_project(): void
    {
        $otherProject = Project::factory()->create(['kode' => 'PRJ-002']);
        Payable::factory()->create([
            'project_id' => $this->project->id,
            'akun_id' => $this->akun->id,
            'pihak_type_id' => $this->vendorType->id,
            'pihak_item_id' => $this->vendor->id,
            'nomor_invoice' => 'INV-GLOBAL',
        ]);
        $path = $this->storeXlsx('payable-global-duplicate.xlsx', [
            ['Project Code', 'Akun Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['PRJ-002', 'AKUN-001', 'VENDOR', 'PT Vendor Utama', '2026-08-15', 'INV-GLOBAL', '2026-09-14', 50000000, 0, 'Duplicate across projects'],
        ]);

        $import = new PayableImport(true);
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertCount(1, $import->failures);
        $this->assertStringContainsString('global unique', $import->failures[0]['reason']);
        $this->assertDatabaseMissing('payables', ['project_id' => $otherProject->id, 'nomor_invoice' => 'INV-GLOBAL']);
    }

    /** @test */
    public function test_import_with_project_code_missing_project_fails(): void
    {
        $path = $this->storeXlsx('payable-missing-project.xlsx', [
            ['Project Code', 'Akun Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['PRJ-999', 'AKUN-001', 'VENDOR', 'PT Vendor Utama', '2026-08-15', 'INV-2026-003', '2026-09-14', 50000000, 0, 'Project not found'],
        ]);

        $import = new PayableImport(true);
        Excel::import($import, $path);

        $this->assertEquals(0, $import->successCount);
        $this->assertCount(1, $import->failures);
        $this->assertStringContainsString('not found', $import->failures[0]['reason']);
    }

    /** @test */
    public function test_import_without_project_code_empty_project_code_allowed(): void
    {
        $path = $this->storeXlsx('payable-empty-project.xlsx', [
            ['Akun Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['AKUN-001', 'VENDOR', 'PT Vendor Utama', '2026-08-15', 'INV-2026-004', '2026-09-14', 50000000, 0, 'Empty project code'],
        ]);

        $import = new PayableImport(false);
        Excel::import($import, $path);

        $this->assertEquals(1, $import->successCount);
        $this->assertEmpty($import->failures);
    }

    /** @test */
    public function test_template_download_with_project_code(): void
    {
        $response = $this->actingAs($this->user)->get(route('imports.payables.template', ['use_project_code' => true]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /** @test */
    public function test_template_download_without_project_code(): void
    {
        $response = $this->actingAs($this->user)->get(route('imports.payables.template', ['use_project_code' => false]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_template_headers_match_both_payable_import_modes(): void
    {
        $withProjectTemplate = (new PayableTemplateExport(true))->array();
        $withoutProjectTemplate = (new PayableTemplateExport(false))->array();
        $withProject = $withProjectTemplate[0];
        $withoutProject = $withoutProjectTemplate[0];

        $this->assertSame(
            ['Project Code', 'Akun Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            $withProject,
        );
        $this->assertSame(
            ['Akun Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            $withoutProject,
        );
        $this->assertCount(1, $withProjectTemplate, 'Template should not include sample transactions or notes.');
        $this->assertCount(1, $withoutProjectTemplate, 'Template should not include sample transactions or notes.');
    }

    public function test_generated_payable_templates_import_in_both_modes(): void
    {
        foreach ([true, false] as $useProjectCode) {
            $headers = (new PayableTemplateExport($useProjectCode))->array()[0];
            $values = [
                'Project Code' => 'PRJ-001',
                'Akun Code' => 'AKUN-001',
                'Party Type Code' => 'VENDOR',
                'Party Name' => 'PT Vendor Utama',
                'Date' => '2026-08-15',
                'Invoice No.' => $useProjectCode ? 'INV-TEMPLATE-WITH' : 'INV-TEMPLATE-WITHOUT',
                'Due Date' => '2026-09-14',
                'Amount' => 50000000,
                'Paid' => 0,
                'Description' => 'Template round trip',
            ];
            $path = $this->storeXlsx(
                $useProjectCode ? 'payable-template-with-project.xlsx' : 'payable-template-without-project.xlsx',
                [$headers, array_map(fn (string $header) => $values[$header], $headers)],
            );

            $import = new PayableImport($useProjectCode);
            Excel::import($import, $path);

            $this->assertSame(1, $import->successCount);
            $this->assertEmpty($import->failures);
            $this->assertNull($import->fatalError);
            $this->assertDatabaseHas('payables', [
                'project_id' => $useProjectCode ? $this->project->id : null,
                'akun_id' => $this->akun->id,
                'nomor_invoice' => $values['Invoice No.'],
            ]);
        }
    }

    public function test_payable_template_link_tracks_the_selected_mode(): void
    {
        Livewire::actingAs($this->user)
            ->test(ImportPayables::class)
            ->assertSet('importMode', 'with_project')
            ->assertSee('use_project_code=1', false)
            ->set('importMode', 'without_project')
            ->assertSee('use_project_code=0', false);
    }

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
}
