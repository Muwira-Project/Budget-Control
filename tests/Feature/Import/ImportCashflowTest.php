<?php

namespace Tests\Feature\Import;

use App\Imports\CashflowImport;
use App\Models\Akun;
use App\Models\CashAccount;
use App\Models\Cashflow;
use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportCashflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CashAccount $cashAccount;
    private Akun $akunIncome;
    private Akun $akunExpense;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->cashAccount = CashAccount::factory()->create(['kode' => 'BANK-001', 'status' => 'active']);
        $this->akunIncome = Akun::factory()->create(['kode_akun' => '4-101', 'jenis_akun' => 'pendapatan']);
        $this->akunExpense = Akun::factory()->create(['kode_akun' => '5-101', 'jenis_akun' => 'pengeluaran']);
        $this->project = Project::factory()->create(['kode' => 'PRJ-001']);
    }

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

    public function test_import_with_project(): void
    {
        $this->actingAs($this->user);

        $path = $this->storeXlsx('cashflow-project.xlsx', [
            ['Date', 'Type', 'Source', 'Cash Account', 'Account (COA)', 'Project', 'Party Type', 'Party', 'Amount', 'Description'],
            ['2026-08-15', 'masuk', 'pendapatan', 'BANK-001', '4-101', 'PRJ-001', '', '', 50000000, 'Termin 1 Proyek'],
        ]);

        $import = new CashflowImport;
        Excel::import($import, $path);

        $this->assertSame(1, $import->successCount);
        $this->assertDatabaseHas('cashflows', [
            'tanggal' => '2026-08-15 00:00:00',
            'jenis' => 'masuk',
            'sumber' => 'pendapatan',
            'cash_account_id' => $this->cashAccount->id,
            'akun_id' => $this->akunIncome->id,
            'project_id' => $this->project->id,
            'nominal' => 50000000,
            'status' => 'draft',
        ]);
    }

    public function test_import_non_project_blank_and_keywords(): void
    {
        $this->actingAs($this->user);

        $path = $this->storeXlsx('cashflow-non-project.xlsx', [
            ['Date', 'Type', 'Source', 'Cash Account', 'Account (COA)', 'Project', 'Party Type', 'Party', 'Amount', 'Description'],
            ['2026-08-16', 'keluar', 'pengeluaran_lain', 'BANK-001', '5-101', '', '', '', 2500000, 'Operasional Blank'],
            ['2026-08-17', 'keluar', 'pengeluaran_lain', 'BANK-001', '5-101', '-', '', '', 3000000, 'Operasional Dash'],
            ['2026-08-18', 'keluar', 'pengeluaran_lain', 'BANK-001', '5-101', 'NON-PROJECT', '', '', 4500000, 'Operasional Non-Project'],
            ['2026-08-19', 'keluar', 'pengeluaran_lain', 'BANK-001', '5-101', 'none', '', '', 1500000, 'Operasional None'],
        ]);

        $import = new CashflowImport;
        Excel::import($import, $path);

        $this->assertSame(4, $import->successCount);
        $this->assertCount(0, $import->failures);

        // Verify that all 4 entries were saved with project_id = null
        $cashflows = Cashflow::whereNull('project_id')->get();
        $this->assertCount(4, $cashflows);
    }

    public function test_import_validation_fails_for_invalid_project_or_akun(): void
    {
        $this->actingAs($this->user);

        $path = $this->storeXlsx('cashflow-invalid.xlsx', [
            ['Date', 'Type', 'Source', 'Cash Account', 'Account (COA)', 'Project', 'Party Type', 'Party', 'Amount', 'Description'],
            ['2026-08-15', 'masuk', 'pendapatan', 'BANK-001', 'INVALID-COA', '', '', '', 1000000, 'Invalid Akun'],
            ['2026-08-15', 'masuk', 'pendapatan', 'BANK-001', '4-101', 'PRJ-NONEXISTENT', '', '', 1000000, 'Invalid Project'],
            ['2026-08-15', 'invalid_type', 'pendapatan', 'BANK-001', '4-101', '', '', '', 1000000, 'Invalid Type'],
        ]);

        $import = new CashflowImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertCount(3, $import->failures);
    }

    public function test_cashflow_template_download(): void
    {
        $response = $this->actingAs($this->user)->get(route('imports.cashflows.template'));
        $response->assertOk();
    }
}
