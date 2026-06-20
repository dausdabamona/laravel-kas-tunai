<?php

namespace App\Livewire;

use App\Enums\Sumber;
use App\Services\LaporanService;
use Livewire\Component;

/**
 * Pilih rentang -> rekap per sumber + tautan cetak BKU/LPJ.
 */
class Laporan extends Component
{
    public string $dari = '';

    public string $sampai = '';

    public array $rekap = [];

    public function mount(): void
    {
        $this->dari = now()->startOfMonth()->format('Y-m-d');
        $this->sampai = now()->endOfMonth()->format('Y-m-d');
    }

    public function render(LaporanService $laporan)
    {
        $this->rekap = $laporan->rekap($this->dari, $this->sampai);

        return view('livewire.laporan', [
            'sumberPilihan' => Sumber::cases(),
        ])->layout('layouts.app');
    }
}
