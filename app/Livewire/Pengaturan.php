<?php

namespace App\Livewire;

use App\Models\Pengaturan as PengaturanModel;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Kelola pengaturan aplikasi — saat ini batas periode terkunci.
 * Hanya bendahara/ppk (ability kunci-periode).
 */
class Pengaturan extends Component
{
    #[Validate('nullable|date')]
    public string $periodeTerkunciHingga = '';

    public function mount(): void
    {
        $this->periodeTerkunciHingga = PengaturanModel::get('periode_terkunci_hingga', '') ?? '';
    }

    public function simpan(): void
    {
        $this->authorize('kunci-periode');
        $this->validate();

        PengaturanModel::set('periode_terkunci_hingga', $this->periodeTerkunciHingga ?: null);
        $this->dispatch('pengaturan-tersimpan');
    }

    public function render()
    {
        return view('livewire.pengaturan')->layout('layouts.app');
    }
}
