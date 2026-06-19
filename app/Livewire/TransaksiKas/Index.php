<?php

namespace App\Livewire\TransaksiKas;

use App\Enums\StatusSpj;
use App\Enums\Sumber;
use App\Models\TransaksiKas;
use App\Services\SaldoService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $cari = '';

    #[Url(as: 'sumber')]
    public string $filterSumber = '';

    #[Url(as: 'dari')]
    public string $filterDariTanggal = '';

    #[Url(as: 'sampai')]
    public string $filterSampaiTanggal = '';

    #[Url(as: 'status')]
    public string $filterStatusSpj = '';

    public array $rekap = ['tunai' => 0, 'bank' => 0, 'total' => 0];

    public function mount(SaldoService $saldo): void
    {
        $this->rekap = $saldo->rekap();
    }

    #[On('transaksi-tersimpan')]
    public function segarkan(SaldoService $saldo): void
    {
        $this->rekap = $saldo->rekap();
        $this->resetPage();
    }

    public function updatingCari(): void
    {
        $this->resetPage();
    }

    public function updatingFilterSumber(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatusSpj(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function transaksi()
    {
        return TransaksiKas::query()
            ->when($this->filterSumber, fn ($q) => $q->where('sumber', $this->filterSumber))
            ->when($this->filterStatusSpj, fn ($q) => $q->where('status_spj', $this->filterStatusSpj))
            ->when($this->cari, fn ($q) => $q->where('kegiatan', 'like', "%{$this->cari}%"))
            ->when($this->filterDariTanggal, fn ($q) => $q->whereDate('tanggal', '>=', $this->filterDariTanggal))
            ->when($this->filterSampaiTanggal, fn ($q) => $q->whereDate('tanggal', '<=', $this->filterSampaiTanggal))
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25);
    }

    public function hapus(int $id): void
    {
        $trx = TransaksiKas::findOrFail($id);
        $this->authorize('delete', $trx);

        $trx->delete();
        $this->dispatch('transaksi-tersimpan');
    }

    public function render()
    {
        return view('livewire.transaksi-kas.index', [
            'sumberPilihan' => Sumber::cases(),
            'statusSpjPilihan' => StatusSpj::cases(),
        ])->layout('layouts.app');
    }
}
