<?php

namespace App\Livewire;

use App\Services\ImporBankService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Impor mutasi rekening koran (.xlsx) → pratinjau + pemetaan kolom → impor.
 *
 * Pemetaan kolom dapat di-override manual karena format antar bank berbeda
 * (BNI/BSI dst). Hanya bendahara. Dedup & period-guard di ImporBankService.
 */
class ImporRekKoran extends Component
{
    use WithFileUploads;

    // extensions (bukan mimes): xlsx kerap terdeteksi application/zip oleh finfo.
    #[Validate('required|file|extensions:xlsx,xls|max:10240')]
    public $file;

    /** Pemetaan indeks kolom; tebakan default, dapat diubah pengguna. */
    public array $peta = ['tanggal' => 0, 'uraian' => 1, 'debet' => 2, 'kredit' => 3];

    /** @var array<int, array{tanggal:string, uraian:string, debet:int, kredit:int}> */
    public array $pratinjau = [];

    public ?array $ringkasan = null;

    public function pratinjau(ImporBankService $impor): void
    {
        $this->authorize('impor-bank');
        $this->validate();

        $this->pratinjau = $impor->parse($this->file, $this->peta)->all();
        $this->ringkasan = null;
    }

    public function impor(ImporBankService $impor): void
    {
        $this->authorize('impor-bank');

        $this->ringkasan = $impor->impor($this->pratinjau);
        $this->dispatch('impor-selesai');
    }

    public function render()
    {
        return view('livewire.impor-rek-koran')->layout('layouts.app');
    }
}
