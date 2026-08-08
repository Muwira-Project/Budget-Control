<?php

namespace Tests\Feature;

use App\Livewire\Kategoris\Create as CreateKategori;
use App\Livewire\Kategoris\Edit as EditKategori;
use App\Livewire\Kategoris\Index as IndexKategori;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KategoriTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('kategoris.index'))->assertRedirect(route('login'));
    }

    public function test_index_page_renders_for_authenticated_user(): void
    {
        $user = User::factory()->admin()->create();
        Kategori::factory()->create();

        $this->actingAs($user)
            ->get(route('kategoris.index'))
            ->assertOk();
    }

    public function test_kategori_can_be_created(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateKategori::class)
            ->set('kode', 'KAT-001')
            ->set('nama', 'Material')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('kategoris.index'));

        $this->assertDatabaseHas('kategoris', ['kode' => 'KAT-001', 'nama' => 'Material']);
    }

    public function test_kategori_kode_must_be_unique(): void
    {
        $user = User::factory()->create();
        Kategori::factory()->create(['kode' => 'KAT-001']);

        Livewire::actingAs($user)
            ->test(CreateKategori::class)
            ->set('kode', 'KAT-001')
            ->set('nama', 'Jasa')
            ->call('save')
            ->assertHasErrors(['kode']);
    }

    public function test_kategori_can_be_updated(): void
    {
        $user = User::factory()->create();
        $kategori = Kategori::factory()->create();

        Livewire::actingAs($user)
            ->test(EditKategori::class, ['kategori' => $kategori])
            ->set('nama', 'Material Bangunan')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('kategoris.index'));

        $this->assertDatabaseHas('kategoris', ['id' => $kategori->id, 'nama' => 'Material Bangunan']);
    }

    public function test_kategori_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $kategori = Kategori::factory()->create();

        Livewire::actingAs($user)
            ->test(IndexKategori::class)
            ->call('delete', $kategori->id);

        $this->assertDatabaseMissing('kategoris', ['id' => $kategori->id]);
    }

    public function test_kategori_can_be_searched(): void
    {
        $user = User::factory()->create();
        Kategori::factory()->create(['kode' => 'KAT-001', 'nama' => 'Material']);
        Kategori::factory()->create(['kode' => 'KAT-002', 'nama' => 'Transportasi']);

        Livewire::actingAs($user)
            ->test(IndexKategori::class)
            ->set('search', 'Material')
            ->assertSee('Material')
            ->assertDontSee('Transportasi');
    }
}
