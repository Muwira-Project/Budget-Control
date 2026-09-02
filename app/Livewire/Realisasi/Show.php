<?php

namespace App\Livewire\Realisasi;

use App\Models\Realisasi;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Realisasi $realisasi;

    public function mount(Realisasi $realisasi): void
    {
        $this->realisasi = $realisasi->load([
            'project', 'akun', 'kategori', 'pihakType', 'pihakItem',
        ]);
    }

    public function render()
    {
        return view('livewire.realisasi.show');
    }
}
