<?php

namespace App\Livewire;

use App\Enums\KategoriLampiran;
use App\Models\Lampiran;
use App\Models\TransaksiKas;
use App\Services\LampiranService;
use App\Services\PengembalianService;
use App\Services\RekonsiliasiService;
use App\Services\TambahanService;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Panel rekonsiliasi kas uang muka untuk satu transaksi belanja.
 *
 * Tampilkan selisih (uang muka vs nota + pengembalian + tambahan) lalu sediakan
 * dua aksi penyeimbang: catat PENGEMBALIAN (sisa lebih balik ke kas) dan catat
 * TAMBAHAN (bendahara menalangi kekurangan), masing-masing dengan bukti tanda
 * terima. GUARD: pengembalian hanya saat ada sisa (selisih > 0); tambahan hanya
 * saat ada kekurangan (selisih < 0) — mencegah keadaan tak konsisten saat uang
 * muka sudah sama dengan nota.
 */
class Rekonsiliasi extends Component
{
    use WithFileUploads;

    public TransaksiKas $transaksi;

    // Form pengembalian (sisa lebih).
    public string $tglPengembalian = '';

    public int $jumlahPengembalian = 0;

    public string $ketPengembalian = '';

    // Form tambahan (kekurangan).
    public string $tglTambahan = '';

    public int $jumlahTambahan = 0;

    public string $ketTambahan = '';

    // Bukti tanda terima (foto/PDF) per baris pengembalian/tambahan.
    public $buktiRekon;

    public function mount(TransaksiKas $transaksi): void
    {
        $this->transaksi = $transaksi;
        $this->tglPengembalian = $transaksi->tanggal->format('Y-m-d');
        $this->tglTambahan = $transaksi->tanggal->format('Y-m-d');
    }

    public function catatPengembalian(PengembalianService $service): void
    {
        $this->authorize('update', $this->transaksi);
        $this->validate([
            'tglPengembalian' => ['required', 'date'],
            'jumlahPengembalian' => ['required', 'integer', 'min:1'],
            'ketPengembalian' => ['nullable', 'string', 'max:255'],
        ]);

        // GUARD: hanya boleh mengembalikan sebesar SISA yang masih ada.
        $selisih = app(RekonsiliasiService::class)->untuk($this->transaksi)['selisih'];

        if ($selisih <= 0) {
            $this->addError('jumlahPengembalian', 'Tidak ada sisa untuk dikembalikan (uang muka sudah sama atau kurang dari total nota).');

            return;
        }

        if ($this->jumlahPengembalian > $selisih) {
            $this->addError('jumlahPengembalian', 'Maksimal pengembalian Rp '.number_format($selisih, 0, ',', '.').' (sisa uang muka).');

            return;
        }

        try {
            $service->catat($this->transaksi, [
                'tanggal' => $this->tglPengembalian,
                'jumlah' => $this->jumlahPengembalian,
                'keterangan' => $this->ketPengembalian ?: null,
            ]);
        } catch (AuthorizationException $e) {
            $this->addError('tglPengembalian', $e->getMessage());

            return;
        }

        $this->reset(['jumlahPengembalian', 'ketPengembalian']);
        $this->dispatch('rekonsiliasi-tersimpan');
    }

    public function catatTambahan(TambahanService $service): void
    {
        $this->authorize('update', $this->transaksi);
        $this->validate([
            'tglTambahan' => ['required', 'date'],
            'jumlahTambahan' => ['required', 'integer', 'min:1'],
            'ketTambahan' => ['nullable', 'string', 'max:255'],
        ]);

        // GUARD: hanya boleh menambah sebesar KEKURANGAN yang masih ada.
        $selisih = app(RekonsiliasiService::class)->untuk($this->transaksi)['selisih'];

        if ($selisih >= 0) {
            $this->addError('jumlahTambahan', 'Tidak ada kekurangan untuk ditambah (nota belum melebihi uang muka).');

            return;
        }

        $kurang = -$selisih;

        if ($this->jumlahTambahan > $kurang) {
            $this->addError('jumlahTambahan', 'Maksimal penambahan Rp '.number_format($kurang, 0, ',', '.').' (kekurangan).');

            return;
        }

        try {
            $service->catat($this->transaksi, [
                'tanggal' => $this->tglTambahan,
                'jumlah' => $this->jumlahTambahan,
                'keterangan' => $this->ketTambahan ?: null,
            ]);
        } catch (AuthorizationException $e) {
            $this->addError('tglTambahan', $e->getMessage());

            return;
        }

        $this->reset(['jumlahTambahan', 'ketTambahan']);
        $this->dispatch('rekonsiliasi-tersimpan');
    }

    public function hapusPengembalian(int $id): void
    {
        $this->authorize('update', $this->transaksi);
        $this->transaksi->pengembalian()->findOrFail($id)->delete();
    }

    public function hapusTambahan(int $id): void
    {
        $this->authorize('update', $this->transaksi);
        $this->transaksi->tambahan()->findOrFail($id)->delete();
    }

    public function unggahBuktiPengembalian(LampiranService $lampiran, int $id): void
    {
        $this->authorize('update', $this->transaksi);
        $this->validate(['buktiRekon' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120']);

        $p = $this->transaksi->pengembalian()->findOrFail($id);
        $lampiran->simpan($p, $this->buktiRekon, KategoriLampiran::Kuitansi);

        $this->reset('buktiRekon');
    }

    public function unggahBuktiTambahan(LampiranService $lampiran, int $id): void
    {
        $this->authorize('update', $this->transaksi);
        $this->validate(['buktiRekon' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120']);

        $t = $this->transaksi->tambahan()->findOrFail($id);
        $lampiran->simpan($t, $this->buktiRekon, KategoriLampiran::Kuitansi);

        $this->reset('buktiRekon');
    }

    public function hapusBuktiRekon(LampiranService $lampiran, int $lampiranId): void
    {
        $this->authorize('update', $this->transaksi);
        $lampiran->hapus(Lampiran::findOrFail($lampiranId));
    }

    /**
     * Panel di-refresh saat rincian nota ATAU header transaksi (kredit/uang muka)
     * berubah — sehingga uang muka, selisih, status & tautan Cetak Tanda Terima
     * selalu memakai nilai terbaru.
     */
    #[On('rincian-nota-berubah')]
    #[On('transaksi-diperbarui')]
    public function refreshPanel(): void
    {
        // Tidak perlu aksi — Livewire merender ulang & render() membaca ulang selisih.
    }

    public function isiOtomatis(string $jenis): void
    {
        // Prefill jumlah dari selisih agar bendahara tinggal konfirmasi.
        $selisih = app(RekonsiliasiService::class)->untuk($this->transaksi)['selisih'];

        if ($jenis === 'pengembalian' && $selisih > 0) {
            $this->jumlahPengembalian = $selisih;
        } elseif ($jenis === 'tambahan' && $selisih < 0) {
            $this->jumlahTambahan = -$selisih;
        }
    }

    public function render(RekonsiliasiService $rekonsiliasi, LampiranService $lampiran)
    {
        $this->transaksi->refresh();

        return view('livewire.rekonsiliasi', [
            'rekon' => $rekonsiliasi->untuk($this->transaksi),
            'daftarPengembalian' => $this->transaksi->pengembalian()->with('lampiran')->orderBy('urutan')->get(),
            'daftarTambahan' => $this->transaksi->tambahan()->with('lampiran')->orderBy('urutan')->get(),
            'urlBukti' => fn (Lampiran $l) => $lampiran->urlSementara($l),
        ]);
    }
}
