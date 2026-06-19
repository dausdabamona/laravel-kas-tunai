<?php

namespace App\Livewire;

use App\Enums\KategoriLampiran;
use App\Models\Lampiran;
use App\Models\TransaksiKas;
use App\Services\LampiranService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Kelola bukti perjalanan dinas (tiket/boarding) pada satu transaksi PD.
 * Murni reuse LampiranService (kompresi queue / PDF pass-through) + unduh ZIP.
 */
class BuktiPerjalanan extends Component
{
    use WithFileUploads;

    public TransaksiKas $transaksi;

    #[Validate('required|file|max:5120')]
    public $berkas;

    public function mount(TransaksiKas $transaksi): void
    {
        $this->transaksi = $transaksi;
    }

    public function unggah(LampiranService $lampiran): void
    {
        $this->authorize('update', $this->transaksi);
        $this->validate();

        $lampiran->simpan($this->transaksi, $this->berkas, KategoriLampiran::BuktiPd);
        $this->reset('berkas');
    }

    public function hapus(LampiranService $lampiran, int $id): void
    {
        $this->authorize('update', $this->transaksi);
        $lampiran->hapus(Lampiran::findOrFail($id));
    }

    public function render(LampiranService $lampiran)
    {
        $bukti = $this->transaksi->lampiran()
            ->where('kategori', KategoriLampiran::BuktiPd->value)
            ->orderBy('urutan')->get();

        return view('livewire.bukti-perjalanan', [
            'daftarBukti' => $bukti,
            'urlBukti' => fn (Lampiran $l) => $lampiran->urlSementara($l),
            'urlZip' => $bukti->isNotEmpty() ? $lampiran->urlZipBukti($this->transaksi) : null,
        ]);
    }
}
