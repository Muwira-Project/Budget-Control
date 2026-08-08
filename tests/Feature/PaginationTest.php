<?php

namespace Tests\Feature;

use App\Livewire\Akuns\Index as AkunIndex;
use App\Models\Akun;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_page_size_is_ten(): void
    {
        $user = User::factory()->create();
        Akun::factory()->count(5)->create();

        $component = Livewire::actingAs($user)->test(AkunIndex::class);

        $component->assertSet('perPage', 10);
        $this->assertSame(10, $component->instance()->akuns()->perPage());
    }

    public function test_per_page_selector_changes_the_page_size(): void
    {
        $user = User::factory()->create();
        Akun::factory()->count(30)->create();

        $component = Livewire::actingAs($user)->test(AkunIndex::class);
        $component->set('perPage', 25);

        $component->assertSet('perPage', 25);
        $this->assertSame(25, $component->instance()->akuns()->perPage());
    }

    public function test_jump_to_page_works(): void
    {
        $user = User::factory()->create();
        Akun::factory()->count(30)->create();

        $component = Livewire::actingAs($user)->test(AkunIndex::class);
        $component->set('jumpToPage', 3)->call('jumpTo');

        $this->assertSame(3, $component->instance()->akuns()->currentPage());
    }

    public function test_jump_to_page_is_clamped_to_a_positive_number(): void
    {
        $user = User::factory()->create();
        Akun::factory()->count(30)->create();

        $component = Livewire::actingAs($user)->test(AkunIndex::class);
        $component->set('jumpToPage', 0)->call('jumpTo');

        $this->assertSame(1, $component->instance()->akuns()->currentPage());
    }

    public function test_footer_is_rendered_on_index_pages(): void
    {
        $user = User::factory()->admin()->create();
        Vendor::factory()->count(12)->create();

        $this->actingAs($user)
            ->get(route('vendors.index'))
            ->assertOk()
            ->assertSee('Rows');
    }
}
