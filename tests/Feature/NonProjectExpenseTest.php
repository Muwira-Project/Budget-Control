<?php

namespace Tests\Feature;

use App\Livewire\NonProjectExpenses\Create as CreateNonProjectExpense;
use App\Livewire\NonProjectExpenses\Edit as EditNonProjectExpense;
use App\Livewire\NonProjectExpenses\Index as IndexNonProjectExpense;
use App\Models\Akun;
use App\Models\NonProjectExpense;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NonProjectExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('non-project-expenses.index'))->assertRedirect(route('login'));
    }

    public function test_staff_cannot_access_non_project_expenses(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->get(route('non-project-expenses.index'))
            ->assertForbidden();
    }

    public function test_index_page_renders_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        NonProjectExpense::factory()->create();

        $this->actingAs($admin)
            ->get(route('non-project-expenses.index'))
            ->assertOk()
            ->assertSee('Non-Project Expense');
    }

    public function test_admin_can_create_non_project_expense(): void
    {
        $admin = User::factory()->admin()->create();
        $akun = Akun::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateNonProjectExpense::class)
            ->set('tanggal', '2026-08-01')
            ->set('akunId', $akun->id)
            ->set('nominal', '5000000')
            ->set('keterangan', 'Listrik kantor')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('non-project-expenses.index'));

        $this->assertDatabaseHas('non_project_expenses', [
            'akun_id' => $akun->id,
            'nominal' => 5000000,
            'created_by' => $admin->id,
        ]);
    }

    public function test_create_requires_account_and_amount(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(CreateNonProjectExpense::class)
            ->set('tanggal', '2026-08-01')
            ->set('nominal', '0')
            ->call('save')
            ->assertHasErrors(['akun_id']);
    }

    public function test_create_rejects_more_than_one_party(): void
    {
        $admin = User::factory()->admin()->create();
        $akun = Akun::factory()->create();
        $vendor = Vendor::factory()->create();
        $supplier = Supplier::factory()->create();

        Livewire::actingAs($admin)
            ->test(CreateNonProjectExpense::class)
            ->set('tanggal', '2026-08-01')
            ->set('akunId', $akun->id)
            ->set('pihakJenis', 'vendor')
            ->set('vendorId', $vendor->id)
            ->set('supplierId', $supplier->id)
            ->set('nominal', '5000000')
            ->call('save')
            ->assertHasErrors(['vendor_id']);
    }

    public function test_admin_can_edit_non_project_expense(): void
    {
        $admin = User::factory()->admin()->create();
        $expense = NonProjectExpense::factory()->create();
        $akun = Akun::factory()->create();

        Livewire::actingAs($admin)
            ->test(EditNonProjectExpense::class, ['expense' => $expense])
            ->set('akunId', $akun->id)
            ->set('nominal', '7500000')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('non-project-expenses.index'));

        $this->assertDatabaseHas('non_project_expenses', ['id' => $expense->id, 'akun_id' => $akun->id, 'nominal' => 7500000]);
    }

    public function test_model_rejects_multiple_parties(): void
    {
        $akun = Akun::factory()->create();
        $vendor = Vendor::factory()->create();
        $supplier = Supplier::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        NonProjectExpense::create([
            'tanggal' => '2026-08-01',
            'akun_id' => $akun->id,
            'vendor_id' => $vendor->id,
            'supplier_id' => $supplier->id,
            'nominal' => 1000000,
        ]);
    }

    public function test_admin_can_delete_non_project_expense(): void
    {
        $admin = User::factory()->admin()->create();
        $expense = NonProjectExpense::factory()->create();

        Livewire::actingAs($admin)
            ->test(IndexNonProjectExpense::class)
            ->call('delete', $expense->id);

        $this->assertDatabaseMissing('non_project_expenses', ['id' => $expense->id]);
    }
}
