<?php

namespace App\Livewire\Budgeting;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $tab = 'plan';

    /**
     * Render the combined Budgeting page with two tabs.
     */
    public function render()
    {
        return view('livewire.budgeting.index');
    }
}
