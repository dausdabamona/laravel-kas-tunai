<?php

namespace App\Livewire;

use App\Services\SaldoService;
use App\Services\TransaksiService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Form pindah dana antar kas (tarik tunai / setor). Hanya bendahara, dan
 * periode terkunci memblok. Delegasi ke TransaksiService::pindahDana.
 */
class PindahDana extends Component
{
    #[Validate('required|in:TUNAI_BANK,BANK_TUNAI')]
    public string $arah = 'BANK_TUNAI';

    #[Validate('required|integer|min:1')]
    public int $nominal = 0;

    #[Validate('required|date')]
    public string $tanggal = '';

    #[Validate('nullable|string|max:255')]
    public string $keterangan = '';

    public array $rekap = ['tunai' => 0, 'bank' => 0, 'total' => 0];

    public function mount(SaldoService $saldo): void
    {
        $this->tanggal = now()->format('Y-m-d');
        $this->rekap = $saldo->rekap();
    }

    public function simpan(TransaksiService $transaksi, SaldoService $saldo): void
    {
        $this->authorize('pindah-dana'); // hanya bendahara
        $data = $this->validate();
        $this->pastikanPeriodeTerbuka($data['tanggal']);

        $transaksi->pindahDana($data['arah'], $data['nominal'], $data['tanggal'], $data['keterangan'] ?: null);

        $this->rekap = $saldo->rekap();
        $this->reset(['nominal', 'keterangan']);
        $this->dispatch('pindah-dana-tersimpan');
    }

    public function render()
    {
        return view('livewire.pindah-dana')->layout('layouts.app');
    }

    private function pastikanPeriodeTerbuka(string $tanggal): void
    {
        $batas = config('kas.periode_terkunci_hingga');

        if (! empty($batas) && Carbon::parse($tanggal)->lte(Carbon::parse($batas)->endOfDay())) {
            throw new AuthorizationException('Periode terkunci: tidak dapat memindah dana pada periode ini.');
        }
    }
}
