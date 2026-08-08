<?php

namespace Tests\Feature\Import;

use App\Imports\AkunImport;
use App\Models\Akun;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportAkunTest extends TestCase
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

    public function test_valid_akuns_are_imported(): void
    {
        $user = User::factory()->create();
        Kategori::factory()->create(['nama' => 'Material']);

        $path = $this->storeXlsx('akun-valid.xlsx', [
            ['Account Code', 'Account Name', 'Type', 'Category'],
            ['5-100', 'Bahan Baku dan Gudang', 'pengeluaran', 'Material'],
            ['4-100', 'Maintenance', 'pendapatan', ''],
        ]);

        $import = new AkunImport;
        Excel::import($import, $path);

        $this->assertSame(2, $import->successCount);
        $this->assertDatabaseHas('akuns', ['kode_akun' => '5-100', 'nama_akun' => 'Bahan Baku dan Gudang']);
        $this->assertDatabaseHas('akuns', ['kode_akun' => '4-100', 'nama_akun' => 'Maintenance']);
    }

    public function test_kategori_is_linked_when_provided(): void
    {
        $user = User::factory()->create();
        $kategori = Kategori::factory()->create(['nama' => 'Material']);

        $path = $this->storeXlsx('akun-kategori.xlsx', [
            ['Account Code', 'Account Name', 'Type', 'Category'],
            ['5-100', 'Bahan Baku dan Gudang', 'pengeluaran', 'Material'],
        ]);

        $import = new AkunImport;
        Excel::import($import, $path);

        $this->assertSame(1, $import->successCount);
        $this->assertDatabaseHas('akuns', ['kode_akun' => '5-100', 'kategori_id' => $kategori->id]);
    }

    public function test_duplicate_akun_is_rejected(): void
    {
        $user = User::factory()->create();
        Akun::factory()->create(['kode_akun' => '5-100']);

        $path = $this->storeXlsx('akun-duplicate.xlsx', [
            ['Account Code', 'Account Name', 'Type', 'Category'],
            ['5-100', 'Bahan Baku dan Gudang', 'pengeluaran', ''],
        ]);

        $import = new AkunImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertStringContainsString('already registered', $import->failures[0]['reason']);
        $this->assertDatabaseCount('akuns', 1);
    }

    public function test_duplicate_akun_within_file_is_rejected(): void
    {
        $user = User::factory()->create();

        $path = $this->storeXlsx('akun-duplicate-in-file.xlsx', [
            ['Account Code', 'Account Name', 'Type', 'Category'],
            ['5-100', 'Bahan Baku dan Gudang', 'pengeluaran', ''],
            ['5-100', 'Bahan Baku Lagi', 'pengeluaran', ''],
        ]);

        $import = new AkunImport;
        Excel::import($import, $path);

        $this->assertSame(1, $import->successCount);
        $this->assertCount(1, $import->failures);
        $this->assertStringContainsString('duplicated in the file', $import->failures[0]['reason']);
        $this->assertDatabaseCount('akuns', 1);
    }

    public function test_invalid_jenis_fails_the_row(): void
    {
        $user = User::factory()->create();

        $path = $this->storeXlsx('akun-jenis-invalid.xlsx', [
            ['Account Code', 'Account Name', 'Type', 'Category'],
            ['5-100', 'Bahan Baku dan Gudang', 'aset', ''],
        ]);

        $import = new AkunImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertStringContainsString('income or expense', $import->failures[0]['reason']);
    }

    public function test_wrong_header_is_fatal_and_nothing_is_imported(): void
    {
        $user = User::factory()->create();

        $path = $this->storeXlsx('akun-wrong-header.xlsx', [
            ['Code', 'Name', 'Type'],
            ['5-100', 'Bahan Baku dan Gudang', 'pengeluaran'],
        ]);

        $import = new AkunImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertNotNull($import->fatalError);
        $this->assertStringContainsString('The header does not match the template', $import->fatalError);
        $this->assertDatabaseCount('akuns', 0);
    }

    public function test_guest_is_redirected_to_login_from_import_page(): void
    {
        $this->get(route('imports.akuns'))->assertRedirect(route('login'));
    }

    public function test_import_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get(route('imports.akuns'))
            ->assertOk()
            ->assertSee('Import Account');
    }

    public function test_template_can_be_downloaded(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('imports.akuns.template'));

        $response->assertOk();
        $this->assertStringContainsString('template-account.xlsx', $response->headers->get('content-disposition'));
    }
}
