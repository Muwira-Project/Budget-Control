<?php

namespace Tests\Feature\Import;

use App\Exports\ReceivableTemplateExport;
use App\Imports\ReceivableImport;
use App\Livewire\Imports\ImportReceivables;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportReceivableTest extends TestCase
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

        $this->investorType = MasterType::firstOrCreate(
            ['kode' => 'INVESTOR'],
            ['nama' => 'Investor', 'flag_ar' => true, 'flag_ap' => true, 'aktif' => true, 'is_system' => true]
        );

        // Create master items
        $this->vendor = MasterItem::factory()->create(['master_type_id' => $this->vendorType->id, 'nama' => 'PT Vendor Utama', 'flag_ar' => true]);
        $this->investor = MasterItem::factory()->create(['master_type_id' => $this->investorType->id, 'nama' => 'Investor A', 'flag_ar' => true]);

        // Create project
        $this->project = Project::factory()->create(['kode' => 'PRJ-001', 'nama' => 'Project Test']);

        $this->user = User::factory()->create();
    }

    /** @test */
    public function test_import_with_project_code(): void
    {
        $path = $this->storeXlsx('receivable-with-project.xlsx', [
            ['Project Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['PRJ-001', 'VENDOR', 'PT Vendor Utama', '2026-08-15', 'INV-2026-001', '2026-09-14', 50000000, 0, 'Tagihan pertama'],
        ]);

        $import = new ReceivableImport(true);
        Excel::import($import, $path);

        $this->assertEquals(1, $import->successCount);
        $this->assertEmpty($import->failures);
        $this->assertNull($import->fatalError);

        $this->assertDatabaseHas('receivables', [
            'project_id' => $this->project->id,
            'nomor_invoice' => 'INV-2026-001',
            'nominal' => 50000000,
        ]);
    }

    /** @test */
    public function test_import_without_project_code(): void
    {
        $path = $this->storeXlsx('receivable-without-project.xlsx', [
            ['Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['VENDOR', 'PT Vendor Utama', '2026-08-15', 'INV-2026-002', '2026-09-14', 50000000, 0, 'Tagihan pertama'],
        ]);

        $import = new ReceivableImport(false);
        Excel::import($import, $path);

        $this->assertEquals(1, $import->successCount);
        $this->assertEmpty($import->failures);
        $this->assertNull($import->fatalError);

        $this->assertDatabaseHas('receivables', [
            'project_id' => null,
            'nomor_invoice' => 'INV-2026-002',
            'nominal' => 50000000,
        ]);
    }

    /** @test */
    public function test_import_without_project_code_duplicate_invoice_fails(): void
    {
        // Create existing receivable with same invoice (global unique)
        Receivable::factory()->create([
            'nomor_invoice' => 'INV-EXISTING',
            'project_id' => null,
            'pihak_type_id' => $this->vendorType->id,
            'pihak_item_id' => $this->vendor->id,
            'tanggal' => '2026-08-01',
            'nominal' => 10000000,
        ]);

        $path = $this->storeXlsx('receivable-duplicate.xlsx', [
            ['Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['VENDOR', 'PT Vendor Utama', '2026-08-15', 'INV-EXISTING', '2026-09-14', 50000000, 0, 'Duplicate'],
        ]);

        $import = new ReceivableImport(false);
        Excel::import($import, $path);

        $this->assertEquals(0, $import->successCount);
        $this->assertCount(1, $import->failures);
        $this->assertStringContainsString('global unique', $import->failures[0]['reason']);
    }

    public function test_import_with_project_code_rejects_invoice_used_by_another_project(): void
    {
        $otherProject = Project::factory()->create(['kode' => 'PRJ-002']);
        Receivable::factory()->create([
            'project_id' => $this->project->id,
            'nomor_invoice' => 'INV-GLOBAL',
            'pihak_type_id' => $this->vendorType->id,
            'pihak_item_id' => $this->vendor->id,
        ]);
        $path = $this->storeXlsx('receivable-global-duplicate.xlsx', [
            ['Project Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['PRJ-002', 'VENDOR', 'PT Vendor Utama', '2026-08-15', 'INV-GLOBAL', '2026-09-14', 50000000, 0, 'Duplicate across projects'],
        ]);

        $import = new ReceivableImport(true);
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertCount(1, $import->failures);
        $this->assertStringContainsString('global unique', $import->failures[0]['reason']);
        $this->assertDatabaseMissing('receivables', ['project_id' => $otherProject->id, 'nomor_invoice' => 'INV-GLOBAL']);
    }

    /** @test */
    public function test_import_with_project_code_missing_project_fails(): void
    {
        $path = $this->storeXlsx('receivable-missing-project.xlsx', [
            ['Project Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['PRJ-999', 'VENDOR', 'PT Vendor Utama', '2026-08-15', 'INV-2026-003', '2026-09-14', 50000000, 0, 'Project not found'],
        ]);

        $import = new ReceivableImport(true);
        Excel::import($import, $path);

        $this->assertEquals(0, $import->successCount);
        $this->assertCount(1, $import->failures);
        $this->assertStringContainsString('not found', $import->failures[0]['reason']);
    }

    /** @test */
    public function test_import_without_project_code_empty_project_code_allowed(): void
    {
        $path = $this->storeXlsx('receivable-empty-project.xlsx', [
            ['Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            ['VENDOR', 'PT Vendor Utama', '2026-08-15', 'INV-2026-004', '2026-09-14', 50000000, 0, 'Empty project code'],
        ]);

        $import = new ReceivableImport(false);
        Excel::import($import, $path);

        $this->assertEquals(1, $import->successCount);
        $this->assertEmpty($import->failures);
    }

    /** @test */
    public function test_template_download_with_project_code(): void
    {
        $response = $this->actingAs($this->user)->get(route('imports.receivables.template', ['use_project_code' => true]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /** @test */
    public function test_template_download_without_project_code(): void
    {
        $response = $this->actingAs($this->user)->get(route('imports.receivables.template', ['use_project_code' => false]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /** @test */
    public function test_template_download_requires_authentication(): void
    {
        $response = $this->get(route('imports.receivables.template', ['use_project_code' => true]));

        $response->assertRedirect(route('login'));
    }

    public function test_template_headers_match_both_receivable_import_modes(): void
    {
        $withProjectTemplate = (new ReceivableTemplateExport(true))->array();
        $withoutProjectTemplate = (new ReceivableTemplateExport(false))->array();
        $withProject = $withProjectTemplate[0];
        $withoutProject = $withoutProjectTemplate[0];

        $this->assertSame(
            ['Project Code', 'Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            $withProject,
        );
        $this->assertSame(
            ['Party Type Code', 'Party Name', 'Date', 'Invoice No.', 'Due Date', 'Amount', 'Paid', 'Description'],
            $withoutProject,
        );
        $this->assertCount(1, $withProjectTemplate, 'Template should not include sample transactions or notes.');
        $this->assertCount(1, $withoutProjectTemplate, 'Template should not include sample transactions or notes.');
    }

    public function test_generated_receivable_templates_import_in_both_modes(): void
    {
        foreach ([true, false] as $useProjectCode) {
            $headers = (new ReceivableTemplateExport($useProjectCode))->array()[0];
            $values = [
                'Project Code' => 'PRJ-001',
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
                $useProjectCode ? 'receivable-template-with-project.xlsx' : 'receivable-template-without-project.xlsx',
                [$headers, array_map(fn (string $header) => $values[$header], $headers)],
            );

            $import = new ReceivableImport($useProjectCode);
            Excel::import($import, $path);

            $this->assertSame(1, $import->successCount);
            $this->assertEmpty($import->failures);
            $this->assertNull($import->fatalError);
            $this->assertDatabaseHas('receivables', [
                'project_id' => $useProjectCode ? $this->project->id : null,
                'nomor_invoice' => $values['Invoice No.'],
            ]);
        }
    }

    public function test_receivable_template_link_tracks_the_selected_mode(): void
    {
        Livewire::actingAs($this->user)
            ->test(ImportReceivables::class)
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
