<?php

namespace Tests\Feature;

use App\Livewire\Akuns\Create as CreateAkun;
use App\Livewire\Akuns\Edit as EditAkun;
use App\Livewire\Akuns\Index as IndexAkun;
use App\Models\Akun;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AkunTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('akuns.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        Akun::factory()->create();

        $this->actingAs($user)
            ->get(route('akuns.index'))
            ->assertOk();
    }

    public function test_akun_can_be_created(): void
    {
        $user = User::factory()->create();
        $kategori = Kategori::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateAkun::class)
            ->set('kodeAkun', '5-100')
            ->set('namaAkun', 'Bahan Baku dan Gudang')
            ->set('jenisAkun', 'pengeluaran')
            ->set('kategoriId', $kategori->id)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('akuns.index'));

        $this->assertDatabaseHas('akuns', ['kode_akun' => '5-100', 'nama_akun' => 'Bahan Baku dan Gudang']);
    }

    public function test_akun_kode_must_be_unique(): void
    {
        $user = User::factory()->create();
        Akun::factory()->create(['kode_akun' => '5-100']);

        Livewire::actingAs($user)
            ->test(CreateAkun::class)
            ->set('kodeAkun', '5-100')
            ->set('namaAkun', 'Akun Lain')
            ->call('save')
            ->assertHasErrors(['kode_akun']);
    }

    public function test_akun_jenis_must_be_valid(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateAkun::class)
            ->set('kodeAkun', '5-200')
            ->set('namaAkun', 'Akun Tidak Valid')
            ->set('jenisAkun', 'lainnya')
            ->call('save')
            ->assertHasErrors(['jenis_akun']);
    }

    public function test_akun_can_be_updated(): void
    {
        $user = User::factory()->create();
        $akun = Akun::factory()->create();

        Livewire::actingAs($user)
            ->test(EditAkun::class, ['akun' => $akun])
            ->set('namaAkun', 'Nama Akun Baru')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('akuns.index'));

        $this->assertDatabaseHas('akuns', ['id' => $akun->id, 'nama_akun' => 'Nama Akun Baru']);
    }

    public function test_akun_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $akun = Akun::factory()->create();

        Livewire::actingAs($user)
            ->test(IndexAkun::class)
            ->call('delete', $akun->id);

        $this->assertDatabaseMissing('akuns', ['id' => $akun->id]);
    }

    public function test_akun_index_uses_eager_loading(): void
    {
        $user = User::factory()->admin()->create();
        Akun::factory()->count(10)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)
            ->get(route('akuns.index'))
            ->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(5, $queryCount);
    }

    public function test_akun_index_filters_by_jenis(): void
    {
        $user = User::factory()->create();
        Akun::factory()->create(['kode_akun' => '4-100', 'nama_akun' => 'Maintenance', 'jenis_akun' => 'pendapatan']);
        Akun::factory()->create(['kode_akun' => '5-100', 'nama_akun' => 'Bahan Baku dan Gudang', 'jenis_akun' => 'pengeluaran']);

        Livewire::actingAs($user)
            ->test(IndexAkun::class)
            ->set('jenisAkun', 'pendapatan')
            ->assertSee('Maintenance')
            ->assertDontSee('Bahan Baku dan Gudang');
    }
}
