<?php

namespace Tests\Feature\Import;

use App\Imports\KategoriImport;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportKategoriTest extends TestCase
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

    public function test_valid_kategoris_are_imported(): void
    {
        $path = $this->storeXlsx('kategori-valid.xlsx', [
            ['Code', 'Name'],
            ['KAT-01', 'Material & Bahan'],
            ['KAT-02', 'Upah Kerja'],
        ]);

        $import = new KategoriImport;
        Excel::import($import, $path);

        $this->assertSame(2, $import->successCount);
        $this->assertEmpty($import->failures);

        $this->assertDatabaseHas('kategoris', [
            'kode' => 'KAT-01',
            'nama' => 'Material & Bahan',
        ]);
        $this->assertDatabaseHas('kategoris', [
            'kode' => 'KAT-02',
            'nama' => 'Upah Kerja',
        ]);
    }

    public function test_kategoris_upsert_existing_records(): void
    {
        Kategori::create([
            'kode' => 'KAT-01',
            'nama' => 'Old Category Name',
        ]);

        $path = $this->storeXlsx('kategori-upsert.xlsx', [
            ['Code', 'Name'],
            ['KAT-01', 'Updated Category Name'],
            ['KAT-02', 'New Category'],
        ]);

        $import = new KategoriImport;
        Excel::import($import, $path);

        $this->assertSame(2, $import->successCount);
        $this->assertDatabaseCount('kategoris', 2);
        $this->assertDatabaseHas('kategoris', [
            'kode' => 'KAT-01',
            'nama' => 'Updated Category Name',
        ]);
    }

    public function test_template_and_export_routes(): void
    {
        $user = User::factory()->admin()->create();

        Kategori::create([
            'kode' => 'KAT-01',
            'nama' => 'Operasional',
        ]);

        // Test template download
        $templateResponse = $this->actingAs($user)->get(route('imports.kategoris.template'));
        $templateResponse->assertOk();
        $this->assertStringContainsString('template-category.xlsx', $templateResponse->headers->get('content-disposition'));

        // Test export download
        $exportResponse = $this->actingAs($user)->get(route('exports.kategoris'));
        $exportResponse->assertOk();
        $this->assertStringContainsString('Category_', $exportResponse->headers->get('content-disposition'));

        // Test import page loads
        $importPageResponse = $this->actingAs($user)->get(route('imports.kategoris'));
        $importPageResponse->assertOk();
        $importPageResponse->assertSee('Import Category');
    }
}
