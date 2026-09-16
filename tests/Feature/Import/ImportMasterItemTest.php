<?php

namespace Tests\Feature\Import;

use App\Imports\MasterItemImport;
use App\Models\MasterField;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportMasterItemTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_valid_master_items_are_imported_with_dynamic_fields(): void
    {
        $type = MasterType::create([
            'kode' => 'CUSTOM_VENDOR',
            'nama' => 'Custom Vendor',
            'flag_ar' => false,
            'flag_ap' => true,
            'aktif' => true,
        ]);

        $phoneField = MasterField::create([
            'master_type_id' => $type->id,
            'label' => 'Phone',
            'tipe' => 'text',
            'is_required' => false,
            'sort' => 1,
        ]);

        $ratingField = MasterField::create([
            'master_type_id' => $type->id,
            'label' => 'Rating',
            'tipe' => 'number',
            'is_required' => false,
            'sort' => 2,
        ]);

        $path = $this->storeXlsx('master-items-valid.xlsx', [
            ['Code', 'Name', 'Phone', 'Rating', 'AP', 'Status'],
            ['V-001', 'PT Maju Bersama', '0812345678', '4.5', 'Yes', 'Active'],
            ['V-002', 'CV Sukses Abadi', '0898765432', '5', 'No', 'Inactive'],
        ]);

        $import = new MasterItemImport($type);
        Excel::import($import, $path);

        $this->assertSame(2, $import->successCount);
        $this->assertEmpty($import->failures);

        $this->assertDatabaseHas('master_items', [
            'master_type_id' => $type->id,
            'kode' => 'V-001',
            'nama' => 'PT Maju Bersama',
            'flag_ap' => true,
            'aktif' => true,
        ]);

        $item1 = MasterItem::where('kode', 'V-001')->first();
        $this->assertSame('0812345678', $item1->data[(string) $phoneField->id]);
        $this->assertSame(4.5, (float) $item1->data[(string) $ratingField->id]);

        $item2 = MasterItem::where('kode', 'V-002')->first();
        $this->assertFalse($item2->flag_ap);
        $this->assertFalse($item2->aktif);
    }

    public function test_master_items_upsert_existing_records(): void
    {
        $type = MasterType::create([
            'kode' => 'CUSTOM_SUPPLIER',
            'nama' => 'Custom Supplier',
            'flag_ar' => false,
            'flag_ap' => false,
            'aktif' => true,
        ]);

        MasterItem::create([
            'master_type_id' => $type->id,
            'kode' => 'SUP-001',
            'nama' => 'Old Supplier Name',
            'aktif' => true,
        ]);

        $path = $this->storeXlsx('master-items-upsert.xlsx', [
            ['Code', 'Name', 'Status'],
            ['SUP-001', 'New Supplier Name', 'Active'],
            ['SUP-002', 'Brand New Supplier', 'Active'],
        ]);

        $import = new MasterItemImport($type);
        Excel::import($import, $path);

        $this->assertSame(2, $import->successCount);
        $this->assertDatabaseHas('master_items', [
            'kode' => 'SUP-001',
            'nama' => 'New Supplier Name',
        ]);
        $this->assertDatabaseHas('master_items', [
            'kode' => 'SUP-002',
            'nama' => 'Brand New Supplier',
        ]);
    }

    public function test_invalid_rows_are_captured(): void
    {
        $type = MasterType::firstOrCreate(['kode' => 'DIV_TEST'], [
            'nama' => 'Division Test',
            'aktif' => true,
        ]);

        MasterField::create([
            'master_type_id' => $type->id,
            'label' => 'Cost Center',
            'tipe' => 'text',
            'is_required' => true,
            'sort' => 1,
        ]);

        $path = $this->storeXlsx('master-items-invalid.xlsx', [
            ['Code', 'Name', 'Cost Center', 'Status'],
            ['', 'No Code', 'CC-100', 'Active'],
            ['DIV-001', 'Missing CC', '', 'Active'],
            ['DIV-002', 'Valid Division', 'CC-200', 'Active'],
        ]);

        $import = new MasterItemImport($type);
        Excel::import($import, $path);

        $this->assertSame(1, $import->successCount);
        $this->assertCount(2, $import->failures);
        $this->assertDatabaseHas('master_items', ['kode' => 'DIV-002']);
    }

    public function test_template_and_export_routes(): void
    {
        $user = User::factory()->admin()->create();

        $type = MasterType::where('kode', 'MANDOR')->first() ?? MasterType::create([
            'kode' => 'MANDOR',
            'nama' => 'Mandor',
            'aktif' => true,
        ]);

        MasterItem::create([
            'master_type_id' => $type->id,
            'kode' => 'MDR-01',
            'nama' => 'Mandor Sutrisno',
            'aktif' => true,
        ]);

        // Test template download
        $templateResponse = $this->actingAs($user)->get(route('imports.master-items.template', $type));
        $templateResponse->assertOk();
        $this->assertStringContainsString('template-mandor.xlsx', $templateResponse->headers->get('content-disposition'));

        // Test export download
        $exportResponse = $this->actingAs($user)->get(route('exports.master-items', $type));
        $exportResponse->assertOk();
        $this->assertStringContainsString('Mandor_', $exportResponse->headers->get('content-disposition'));

        // Test import page loads
        $importPageResponse = $this->actingAs($user)->get(route('imports.master-items', $type));
        $importPageResponse->assertOk();
        $importPageResponse->assertSee('Import Mandor');
    }
}
