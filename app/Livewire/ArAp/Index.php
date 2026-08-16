<?php

namespace App\Livewire\ArAp;

use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $tab = 'receivable';

    /**
     * Render the combined AR & AP page.
     */
    public function render()
    {
        return view('livewire.ar-ap.index');
    }
}
