<?php

namespace Tests\Feature\Import;

use App\Exports\CashAccountTemplateExport;
use App\Imports\CashAccountImport;
use App\Models\CashAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ImportCashAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_cash_account_and_header_only_template(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $rows = [
            ['Code', 'Name', 'Type', 'Opening Balance', 'Default', 'Status', 'Description'],
            ['BANK-NEW', 'New Bank', 'bank', 1250000, 'yes', 'active', 'Operating account'],
        ];
        $file = storage_path('app/private/cash-account-test.xlsx');
        Excel::store(new class($rows) implements FromArray {
            public function __construct(private array $rows) {}
            public function array(): array { return $this->rows; }
        }, 'cash-account-test.xlsx');

        $import = new CashAccountImport;
        Excel::import($import, $file);

        $this->assertSame(1, $import->successCount);
        $this->assertDatabaseHas('cash_accounts', ['kode' => 'BANK-NEW', 'saldo_awal' => 1250000, 'is_default' => true]);
        $this->assertCount(1, (new CashAccountTemplateExport)->array());
    }

    public function test_duplicate_cash_account_codes_are_rejected(): void
    {
        CashAccount::factory()->create(['kode' => 'BANK-EXISTING']);
        $import = new CashAccountImport;
        $collection = collect([
            collect(['code' => 'BANK-EXISTING', 'name' => 'Existing', 'type' => 'bank', 'opening_balance' => 0, 'default' => 'no', 'status' => 'active', 'description' => '']),
        ]);
        $import->collection($collection);

        $this->assertSame(0, $import->successCount);
        $this->assertStringContainsString('already exists', $import->failures[0]['reason']);
    }

    public function test_cash_account_import_page_and_template_routes_render(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user)->get(route('imports.cash-accounts'))->assertOk();
        $this->get(route('imports.cash-accounts.template'))->assertOk();
    }
}
