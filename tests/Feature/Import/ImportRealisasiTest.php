<?php

namespace Tests\Feature\Import;

use App\Imports\RealisasiImport;
use App\Models\Akun;
use App\Models\Investor;
use App\Models\Kategori;
use App\Models\Mandor;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportRealisasiTest extends TestCase
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

    public function test_valid_realisasi_are_imported(): void
    {
        $user = User::factory()->admin()->create();
        $project = Project::factory()->create(['kode' => 'PRJ-001']);
        $akun = Akun::factory()->create(['kode_akun' => 'AKN-001']);
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 50000000, 'allocation' => 50000000, 'status' => 'approved']);
        $vendor = Vendor::factory()->create(['nama' => 'PT Jasa Konstruksi']);
        $supplier = Supplier::factory()->create(['nama' => 'PT Toko Barang']);
        $material = Kategori::factory()->create(['nama' => 'Material']);
        $jasa = Kategori::factory()->create(['nama' => 'Jasa']);

        $path = $this->storeXlsx('realisasi-valid.xlsx', [
            ['Project Code', 'Account Code', 'Date', 'Vendor', 'Supplier', 'Mandor', 'Investor', 'Amount', 'Description', 'Category'],
            ['PRJ-001', 'AKN-001', '2026-07-01', 'PT Jasa Konstruksi', '', '', '', 25000000, 'Pembayaran material', 'Material'],
            ['PRJ-001', 'AKN-001', '2026-07-15', '', 'PT Toko Barang', '', '', 15000000, '', 'Jasa'],
        ]);

        $import = new RealisasiImport;
        Excel::import($import, $path);

        $this->assertSame(2, $import->successCount);
        $this->assertSame([], $import->failures);
        $this->assertNull($import->fatalError);
        $this->assertDatabaseHas('realisasi', ['project_id' => $project->id, 'akun_id' => $akun->id, 'vendor_id' => $vendor->id, 'kategori_id' => $material->id]);
        $this->assertDatabaseHas('realisasi', ['project_id' => $project->id, 'akun_id' => $akun->id, 'supplier_id' => $supplier->id, 'kategori_id' => $jasa->id]);
    }

    public function test_import_requires_an_approved_allocation_and_non_negative_amount(): void
    {
        $project = Project::factory()->create(['kode' => 'PRJ-001']);
        Akun::factory()->create(['kode_akun' => 'AKN-001']);
        $vendor = Vendor::factory()->create(['nama' => 'PT Jasa']);

        $path = $this->storeXlsx('realisasi-unapproved.xlsx', [
            ['Project Code', 'Account Code', 'Date', 'Vendor', 'Supplier', 'Mandor', 'Investor', 'Amount', 'Description', 'Category'],
            ['PRJ-001', 'AKN-001', '2026-07-01', 'PT Jasa', '', '', '', -1000, 'Test', ''],
        ]);

        $import = new RealisasiImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertStringContainsString('Amount cannot be negative', $import->failures[0]['reason']);
        $this->assertDatabaseCount('realisasi', 0);
    }

    public function test_row_requires_vendor_or_supplier(): void
    {
        $user = User::factory()->create();
        Project::factory()->create(['kode' => 'PRJ-001']);
        Akun::factory()->create(['kode_akun' => 'AKN-001']);

        $path = $this->storeXlsx('realisasi-no-party.xlsx', [
            ['Project Code', 'Account Code', 'Date', 'Vendor', 'Supplier', 'Mandor', 'Investor', 'Amount', 'Description', 'Category'],
            ['PRJ-001', 'AKN-001', '2026-07-01', '', '', '', '', 25000000, 'Test', 'Material'],
        ]);

        $import = new RealisasiImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertStringContainsString('Fill in exactly one party', $import->failures[0]['reason']);
        $this->assertDatabaseCount('realisasi', 0);
    }

    public function test_project_not_found_fails_the_row(): void
    {
        $user = User::factory()->create();
        Project::factory()->create(['kode' => 'PRJ-001']);
        Akun::factory()->create(['kode_akun' => 'AKN-001']);

        $path = $this->storeXlsx('realisasi-project-missing.xlsx', [
            ['Project Code', 'Account Code', 'Date', 'Vendor', 'Supplier', 'Mandor', 'Investor', 'Amount', 'Description', 'Category'],
            ['PRJ-999', 'AKN-001', '2026-07-01', 'PT Supplier', '', '', '', 25000000, 'Test', 'Material'],
        ]);

        $import = new RealisasiImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertCount(1, $import->failures);
        $this->assertStringContainsString('not found', $import->failures[0]['reason']);
        $this->assertDatabaseCount('realisasi', 0);
    }

    public function test_akun_not_found_fails_the_row(): void
    {
        $user = User::factory()->create();
        Project::factory()->create(['kode' => 'PRJ-001']);

        $path = $this->storeXlsx('realisasi-akun-missing.xlsx', [
            ['Project Code', 'Account Code', 'Date', 'Vendor', 'Supplier', 'Mandor', 'Investor', 'Amount', 'Description', 'Category'],
            ['PRJ-001', 'BGT-999', '2026-07-01', 'PT Supplier', '', '', '', 25000000, 'Test', 'Material'],
        ]);

        $import = new RealisasiImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertCount(1, $import->failures);
        $this->assertStringContainsString('not found', $import->failures[0]['reason']);
        $this->assertDatabaseCount('realisasi', 0);
    }

    public function test_vendor_not_found_fails_the_row(): void
    {
        $user = User::factory()->create();
        Project::factory()->create(['kode' => 'PRJ-001']);
        Akun::factory()->create(['kode_akun' => 'AKN-001']);

        $path = $this->storeXlsx('realisasi-vendor-missing.xlsx', [
            ['Project Code', 'Account Code', 'Date', 'Vendor', 'Supplier', 'Mandor', 'Investor', 'Amount', 'Description', 'Category'],
            ['PRJ-001', 'AKN-001', '2026-07-01', 'Vendor Tidak Ada', '', '', '', 25000000, 'Test', 'Material'],
        ]);

        $import = new RealisasiImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertStringContainsString('not found in the vendor master', $import->failures[0]['reason']);
        $this->assertDatabaseCount('realisasi', 0);
    }

    public function test_supplier_not_found_fails_the_row(): void
    {
        $user = User::factory()->create();
        Project::factory()->create(['kode' => 'PRJ-001']);
        Akun::factory()->create(['kode_akun' => 'AKN-001']);

        $path = $this->storeXlsx('realisasi-supplier-missing.xlsx', [
            ['Project Code', 'Account Code', 'Date', 'Vendor', 'Supplier', 'Mandor', 'Investor', 'Amount', 'Description', 'Category'],
            ['PRJ-001', 'AKN-001', '2026-07-01', '', 'Supplier Tidak Ada', '', '', 25000000, 'Test', 'Material'],
        ]);

        $import = new RealisasiImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertStringContainsString('not found in the supplier master', $import->failures[0]['reason']);
        $this->assertDatabaseCount('realisasi', 0);
    }

    public function test_invalid_date_fails_the_row(): void
    {
        $user = User::factory()->create();
        Project::factory()->create(['kode' => 'PRJ-001']);
        Akun::factory()->create(['kode_akun' => 'AKN-001']);

        $path = $this->storeXlsx('realisasi-invalid-date.xlsx', [
            ['Project Code', 'Account Code', 'Date', 'Vendor', 'Supplier', 'Mandor', 'Investor', 'Amount', 'Description', 'Category'],
            ['PRJ-001', 'AKN-001', 'bukan-tanggal', 'PT Supplier', '', '', '', 25000000, 'Test', 'Material'],
        ]);

        $import = new RealisasiImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertStringContainsString('Invalid date', $import->failures[0]['reason']);
    }

    public function test_duplicate_realisasi_is_rejected(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['kode' => 'PRJ-001']);
        $akun = Akun::factory()->create(['kode_akun' => 'AKN-001']);
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 50000000, 'allocation' => 50000000, 'status' => 'approved']);
        $supplier = Supplier::factory()->create(['nama' => 'PT Toko Barang']);
        $kategori = Kategori::factory()->create(['nama' => 'Material']);
        Realisasi::factory()->forSupplier()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'supplier_id' => $supplier->id,
            'kategori_id' => $kategori->id,
            'tanggal' => '2026-07-01',
            'nominal' => 25000000,
            'keterangan' => 'Pembelian material',
        ]);

        $path = $this->storeXlsx('realisasi-duplicate.xlsx', [
            ['Project Code', 'Account Code', 'Date', 'Vendor', 'Supplier', 'Mandor', 'Investor', 'Amount', 'Description', 'Category'],
            ['PRJ-001', 'AKN-001', '2026-07-01', '', 'PT Toko Barang', '', '', 25000000, 'Pembelian material', 'Material'],
        ]);

        $import = new RealisasiImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertStringContainsString('already exists', $import->failures[0]['reason']);
        $this->assertDatabaseCount('realisasi', 1);
    }

    public function test_realisasi_with_mandor_is_imported(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['kode' => 'PRJ-001']);
        $akun = Akun::factory()->create(['kode_akun' => 'AKN-001']);
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 50000000, 'allocation' => 50000000, 'status' => 'approved']);
        $mandor = Mandor::factory()->create(['nama' => 'Budi Santoso']);
        Kategori::factory()->create(['nama' => 'Material']);

        $path = $this->storeXlsx('realisasi-mandor.xlsx', [
            ['Project Code', 'Account Code', 'Date', 'Vendor', 'Supplier', 'Mandor', 'Investor', 'Amount', 'Description', 'Category'],
            ['PRJ-001', 'AKN-001', '2026-07-01', '', '', 'Budi Santoso', '', 25000000, 'Upah mandor', 'Material'],
        ]);

        $import = new RealisasiImport;
        Excel::import($import, $path);

        $this->assertSame(1, $import->successCount);
        $this->assertSame([], $import->failures);
        $this->assertDatabaseHas('realisasi', ['project_id' => $project->id, 'akun_id' => $akun->id, 'mandor_id' => $mandor->id]);
    }

    public function test_realisasi_with_investor_is_imported(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['kode' => 'PRJ-001']);
        $akun = Akun::factory()->create(['kode_akun' => 'AKN-001']);
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 50000000, 'allocation' => 50000000, 'status' => 'approved']);
        $investor = Investor::factory()->create(['nama' => 'PT Mitra Investama']);
        Kategori::factory()->create(['nama' => 'Material']);

        $path = $this->storeXlsx('realisasi-investor.xlsx', [
            ['Project Code', 'Account Code', 'Date', 'Vendor', 'Supplier', 'Mandor', 'Investor', 'Amount', 'Description', 'Category'],
            ['PRJ-001', 'AKN-001', '2026-07-01', '', '', '', 'PT Mitra Investama', 25000000, 'Setoran investor', 'Material'],
        ]);

        $import = new RealisasiImport;
        Excel::import($import, $path);

        $this->assertSame(1, $import->successCount);
        $this->assertSame([], $import->failures);
        $this->assertDatabaseHas('realisasi', ['project_id' => $project->id, 'akun_id' => $akun->id, 'investor_id' => $investor->id]);
    }

    public function test_mandor_not_found_fails_the_row(): void
    {
        $user = User::factory()->create();
        Project::factory()->create(['kode' => 'PRJ-001']);
        Akun::factory()->create(['kode_akun' => 'AKN-001']);

        $path = $this->storeXlsx('realisasi-mandor-missing.xlsx', [
            ['Project Code', 'Account Code', 'Date', 'Vendor', 'Supplier', 'Mandor', 'Investor', 'Amount', 'Description', 'Category'],
            ['PRJ-001', 'AKN-001', '2026-07-01', '', '', 'Mandor Tidak Ada', '', 25000000, 'Test', 'Material'],
        ]);

        $import = new RealisasiImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertStringContainsString('not found in the mandor master', $import->failures[0]['reason']);
        $this->assertDatabaseCount('realisasi', 0);
    }

    public function test_wrong_header_is_fatal_and_nothing_is_imported(): void
    {
        $user = User::factory()->create();
        Akun::factory()->create(['kode_akun' => 'AKN-001']);

        $path = $this->storeXlsx('realisasi-wrong-header.xlsx', [
            ['Account', 'Date', 'Amount'],
            ['AKN-001', '2026-07-01', 25000000],
        ]);

        $import = new RealisasiImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertNotNull($import->fatalError);
        $this->assertStringContainsString('The header does not match the template', $import->fatalError);
        $this->assertDatabaseCount('realisasi', 0);
    }

    public function test_import_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('imports.realisasi'))
            ->assertOk()
            ->assertSee('Import Actual');
    }

    public function test_template_can_be_downloaded(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('imports.realisasi.template'));

        $response->assertOk();
        $this->assertStringContainsString('template-actual.xlsx', $response->headers->get('content-disposition'));
    }
}
