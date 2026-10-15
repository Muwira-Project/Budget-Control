<?php

namespace Tests\Feature\Import;

use App\Imports\FundTransferImport;
use App\Exports\FundTransferTemplateExport;
use App\Models\CashAccount;
use App\Models\FundTransfer;
use App\Models\User;
use App\Livewire\FundTransfers\Index as FundTransferIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Livewire;
use Tests\TestCase;

class ImportFundTransferTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private CashAccount $bank1;
    private CashAccount $bank2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->bank1 = CashAccount::factory()->create(['kode' => 'BANK-001', 'status' => 'active']);
        $this->bank2 = CashAccount::factory()->create(['kode' => 'KAS-001', 'status' => 'active']);
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

    public function test_import_valid_fund_transfers(): void
    {
        $this->actingAs($this->user);

        $path = $this->storeXlsx('fund-transfer-valid.xlsx', [
            ['Date', 'From Cash Account', 'To Cash Account', 'Amount', 'Description'],
            ['2026-08-15', 'BANK-001', 'KAS-001', 5000000, 'Tarik tunai'],
            ['2026-08-16', 'KAS-001', 'BANK-001', 2000000, 'Setor tunai'],
        ]);

        $import = new FundTransferImport;
        Excel::import($import, $path);

        $this->assertSame(2, $import->successCount);
        $this->assertCount(0, $import->failures);

        $this->assertDatabaseHas('fund_transfers', [
            'tanggal' => '2026-08-15 00:00:00',
            'dari_cash_account_id' => $this->bank1->id,
            'ke_cash_account_id' => $this->bank2->id,
            'nominal' => 5000000,
            'status' => 'draft',
        ]);
    }

    public function test_import_fund_transfer_fails_when_same_account(): void
    {
        $this->actingAs($this->user);

        $path = $this->storeXlsx('fund-transfer-same.xlsx', [
            ['Date', 'From Cash Account', 'To Cash Account', 'Amount', 'Description'],
            ['2026-08-15', 'BANK-001', 'BANK-001', 1000000, 'Transfer ke akun sendiri'],
        ]);

        $import = new FundTransferImport;
        Excel::import($import, $path);

        $this->assertSame(0, $import->successCount);
        $this->assertCount(1, $import->failures);
        $this->assertStringContainsString('different', $import->failures[0]['reason']);
    }

    public function test_fund_transfer_template_download(): void
    {
        $response = $this->actingAs($this->user)->get(route('imports.fund-transfers.template'));
        $response->assertOk();
        $this->assertCount(1, (new FundTransferTemplateExport)->array());
    }

    public function test_fund_transfer_import_page_rendered(): void
    {
        $response = $this->actingAs($this->user)->get(route('imports.fund-transfers'));
        $response->assertOk();
    }

    public function test_admin_can_post_imported_fund_transfer_draft(): void
    {
        $admin = User::factory()->admin()->create();
        $transfer = FundTransfer::factory()->create([
            'status' => 'draft',
            'dari_cash_account_id' => $this->bank1->id,
            'ke_cash_account_id' => $this->bank2->id,
        ]);

        Livewire::actingAs($admin)
            ->test(FundTransferIndex::class)
            ->call('postDraft', $transfer->id)
            ->assertSee('Fund transfer posted successfully.');

        $this->assertDatabaseHas('fund_transfers', ['id' => $transfer->id, 'status' => 'posted']);
    }

    public function test_admin_can_bulk_post_only_selected_fund_transfer_drafts(): void
    {
        $admin = User::factory()->admin()->create();
        $draftA = FundTransfer::factory()->create([
            'status' => 'draft', 'dari_cash_account_id' => $this->bank1->id, 'ke_cash_account_id' => $this->bank2->id,
        ]);
        $draftB = FundTransfer::factory()->create([
            'status' => 'draft', 'dari_cash_account_id' => $this->bank2->id, 'ke_cash_account_id' => $this->bank1->id,
        ]);
        $posted = FundTransfer::factory()->create([
            'status' => 'posted', 'dari_cash_account_id' => $this->bank1->id, 'ke_cash_account_id' => $this->bank2->id,
        ]);

        Livewire::actingAs($admin)
            ->test(FundTransferIndex::class)
            ->set('selectedIds', [$draftA->id, $draftB->id, $posted->id])
            ->assertSee('Post selected (2)')
            ->call('postSelected')
            ->assertSee('2 fund transfer(s) posted. 1 non-draft or unavailable row(s) skipped.');

        $this->assertDatabaseHas('fund_transfers', ['id' => $draftA->id, 'status' => 'posted']);
        $this->assertDatabaseHas('fund_transfers', ['id' => $draftB->id, 'status' => 'posted']);
        $this->assertDatabaseHas('fund_transfers', ['id' => $posted->id, 'status' => 'posted']);
    }
}
