<?php

namespace Tests\Feature;

use App\Exports\AkunExport;
use App\Exports\AkunVsRealisasiExport;
use App\Exports\RealisasiExport;
use App\Models\Akun;
use App\Models\Kategori;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use App\Models\ProjectAkun;
use App\Models\Realisasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('exports.page', 'akuns'))->assertRedirect(route('login'));
        $this->get(route('exports.akuns'))->assertRedirect(route('login'));
        $this->get(route('exports.realisasi'))->assertRedirect(route('login'));
        $this->get(route('exports.vs'))->assertRedirect(route('login'));
        $this->get(route('exports.monitoring-summary'))->assertRedirect(route('login'));
    }

    public function test_export_pages_render_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)->get(route('exports.page', 'akuns'))->assertOk()->assertSee('Export Account');
        $this->actingAs($user)->get(route('exports.page', 'realisasi'))->assertOk()->assertSee('Export Actual');
        $this->actingAs($user)->get(route('exports.page', 'vs'))->assertOk()->assertSee('Export Account Detail');
    }

    public function test_akun_excel_download(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('exports.akuns', ['format' => 'xlsx']));

        $response->assertOk();
        $this->assertStringContainsString('Account_'.now()->format('Ymd').'.xlsx', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
    }

    public function test_monitoring_summary_excel_download(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('exports.monitoring-summary', ['format' => 'xlsx']));

        $response->assertOk();
        $this->assertStringContainsString('Monitoring_Summary_'.now()->format('Ymd').'.xlsx', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('content-type'));
    }

    public function test_monitoring_summary_csv_download(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('exports.monitoring-summary', ['format' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('Monitoring_Summary_'.now()->format('Ymd').'.csv', $response->headers->get('content-disposition'));
    }

    public function test_monitoring_summary_pdf_download(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('exports.monitoring-summary', ['format' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('Monitoring_Summary_'.now()->format('Ymd').'.pdf', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_akun_pdf_download(): void
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('exports.akuns', ['format' => 'pdf']));

        $response->assertOk();
        $this->assertStringContainsString('Account_'.now()->format('Ymd').'.pdf', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_realisasi_excel_and_pdf_download(): void
    {
        $user = User::factory()->admin()->create();

        $excel = $this->actingAs($user)->get(route('exports.realisasi', ['format' => 'xlsx']));
        $excel->assertOk();
        $this->assertStringContainsString('Actual_'.now()->format('Ymd').'.xlsx', $excel->headers->get('content-disposition'));

        $pdf = $this->actingAs($user)->get(route('exports.realisasi', ['format' => 'pdf']));
        $pdf->assertOk();
        $this->assertStringContainsString('Actual_'.now()->format('Ymd').'.pdf', $pdf->headers->get('content-disposition'));
    }

    public function test_vs_excel_and_pdf_download(): void
    {
        $user = User::factory()->admin()->create();

        $excel = $this->actingAs($user)->get(route('exports.vs', ['format' => 'xlsx']));
        $excel->assertOk();
        $this->assertStringContainsString('Account_vs_Actual_'.now()->format('Ymd').'.xlsx', $excel->headers->get('content-disposition'));

        $pdf = $this->actingAs($user)->get(route('exports.vs', ['format' => 'pdf']));
        $pdf->assertOk();
        $this->assertStringContainsString('Account_vs_Actual_'.now()->format('Ymd').'.pdf', $pdf->headers->get('content-disposition'));
    }

    public function test_akun_export_contains_expected_columns(): void
    {
        $user = User::factory()->create();
        $kategori = Kategori::factory()->create(['nama' => 'Material']);
        Akun::factory()->create([
            'kode_akun' => 'AKN-001',
            'nama_akun' => 'Biaya Material',
            'jenis_akun' => 'pengeluaran',
            'kategori_id' => $kategori->id,
        ]);

        $export = new AkunExport([]);
        $row = $export->query()->get()->first();

        $this->assertSame(
            ['AKN-001', 'Biaya Material', 'Expense', 'Material'],
            $export->map($row),
        );
    }

    public function test_vs_export_contains_percentage(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['kode' => 'PRJ-001', 'nama' => 'Gedung Kantor']);
        $akun = Akun::factory()->create(['kode_akun' => 'AKN-001', 'nama_akun' => 'Biaya Material']);
        ProjectAkun::create(['project_id' => $project->id, 'akun_id' => $akun->id, 'budget' => 100000000, 'allocation' => 100000000]);
        Realisasi::factory()->create(['project_id' => $project->id, 'akun_id' => $akun->id, 'nominal' => 25000000]);

        $export = new AkunVsRealisasiExport([]);
        $mapped = $export->map($export->query()->get()->first());

        $this->assertSame(['PRJ-001 - Gedung Kantor', 'AKN-001', 'Biaya Material', 100000000.0, 100000000.0, 25000000.0, 75000000.0, 25.0], $mapped);
    }

    public function test_realisasi_export_contains_expected_columns(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['kode' => 'PRJ-001', 'nama' => 'Gedung Kantor']);
        $akun = Akun::factory()->create(['kode_akun' => 'AKN-001', 'nama_akun' => 'Biaya Material']);
        $supplierType = MasterType::firstOrCreate(
            ['kode' => 'SUPPLIER'],
            ['nama' => 'Supplier', 'flag_ar' => true, 'flag_ap' => true, 'aktif' => true, 'is_system' => true],
        );
        $supplier = MasterItem::factory()->create([
            'master_type_id' => $supplierType->id,
            'nama' => 'PT Toko Barang',
            'flag_ar' => true,
            'flag_ap' => true,
        ]);
        Realisasi::factory()->create([
            'project_id' => $project->id,
            'akun_id' => $akun->id,
            'pihak_type_id' => $supplierType->id,
            'pihak_item_id' => $supplier->id,
            'kategori_id' => null,
            'tanggal' => '2026-07-01',
            'nominal' => 30000000,
            'keterangan' => 'Pembayaran',
        ]);

        $export = new RealisasiExport([]);
        $row = $export->query()->get()->first();

        $this->assertSame(
            ['PRJ-001 - Gedung Kantor', 'AKN-001 - Biaya Material', null, '2026-07-01', 'PT Toko Barang', 30000000.0, 'Pembayaran'],
            $export->map($row),
        );
    }
}
