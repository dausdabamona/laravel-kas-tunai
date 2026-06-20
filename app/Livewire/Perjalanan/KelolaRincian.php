<?php

namespace App\Livewire\Perjalanan;

use App\Enums\KategoriLampiran;
use App\Enums\KomponenBiaya;
use App\Enums\PembayarPd;
use App\Enums\Sumber;
use App\Models\Lampiran;
use App\Models\SuratTugas;
use App\Services\LampiranService;
use App\Services\RincianPdService;
use App\Services\UangMukaPdService;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Kelola rincian biaya perjalanan dinas PER PEGAWAI pada satu surat tugas.
 *
 * CRUD RincianPd + bukti per komponen (foto/PDF) + pemberian uang muka
 * (tunai/transfer) yang menggerakkan saldo & dihitung sebagai "telah dibayar"
 * pada kuitansi rampung. Delegasi ke RincianPdService / UangMukaPdService /
 * LampiranService.
 */
class KelolaRincian extends Component
{
    use WithFileUploads;

    public SuratTugas $suratTugas;

    public ?int $editingId = null;

    public int $pegawaiIndex = 0;

    #[Validate('required|string')]
    public string $komponen = KomponenBiaya::UangHarian->value;

    #[Validate('nullable|string|max:255')]
    public string $uraian = '';

    #[Validate('required|integer|min:1')]
    public int $qty = 1;

    #[Validate('required|integer|min:0')]
    public int $harga_satuan = 0;

    #[Validate('required|string')]
    public string $pembayar = PembayarPd::Pelaksana->value;

    #[Validate('required|string')]
    public string $metode = Sumber::Tunai->value;

    #[Validate('nullable|string|max:255')]
    public string $keterangan = '';

    // Bukti per komponen (foto/PDF).
    public $bukti;

    // Form uang muka (tunai/transfer).
    public int $umPegawaiIndex = 0;

    public string $umTanggal = '';

    public int $umJumlah = 0;

    public string $umMetode = Sumber::Tunai->value;

    public string $umKeterangan = '';

    public function mount(SuratTugas $suratTugas): void
    {
        $this->suratTugas = $suratTugas;
        $this->qty = $this->hariDefault(); // uang harian default = durasi surat tugas
        $this->umTanggal = $suratTugas->tgl_berangkat->format('Y-m-d');
    }

    /**
     * Saat komponen berganti, isi qty otomatis dari durasi surat tugas untuk
     * komponen berbasis hari (uang harian / fullboard). Tetap bisa diubah manual
     * agar bisa dikombinasikan (mis. 2 hari harian + 2 hari fullboard).
     */
    public function updatedKomponen(string $value): void
    {
        if ($this->editingId) {
            return;
        }

        $this->qty = in_array($value, [KomponenBiaya::UangHarian->value, KomponenBiaya::Fullboard->value], true)
            ? $this->hariDefault()
            : 1;
    }

    private function hariDefault(): int
    {
        return max(1, (int) $this->suratTugas->lama_hari);
    }

    public function simpan(): void
    {
        $this->authorize('perjalanan-dinas');
        $data = $this->validate();

        $nominal = $this->qty * $this->harga_satuan;
        $namaPegawai = $this->suratTugas->pegawai[$this->pegawaiIndex]['nama'] ?? '';

        $atribut = [
            'pegawai_index' => $this->pegawaiIndex,
            'pegawai_nama' => $namaPegawai,
            'komponen' => $data['komponen'],
            'uraian' => $data['uraian'] ?: null,
            'qty' => $data['qty'],
            'harga_satuan' => $data['harga_satuan'],
            'nominal' => $nominal,
            'pembayar' => $data['pembayar'],
            'metode' => $data['metode'],
            'keterangan' => $data['keterangan'] ?: null,
        ];

        if ($this->editingId) {
            $this->suratTugas->rincian()->findOrFail($this->editingId)->update($atribut);
        } else {
            $atribut['urutan'] = (int) $this->suratTugas->rincian()->where('pegawai_index', $this->pegawaiIndex)->max('urutan') + 1;
            $this->suratTugas->rincian()->create($atribut);
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $r = $this->suratTugas->rincian()->findOrFail($id);
        $this->editingId = $r->id;
        $this->pegawaiIndex = $r->pegawai_index;
        $this->komponen = $r->komponen->value;
        $this->uraian = $r->uraian ?? '';
        $this->qty = $r->qty;
        $this->harga_satuan = $r->harga_satuan;
        $this->pembayar = $r->pembayar->value;
        $this->metode = $r->metode->value;
        $this->keterangan = $r->keterangan ?? '';
    }

    public function hapus(int $id): void
    {
        $this->authorize('perjalanan-dinas');
        $this->suratTugas->rincian()->findOrFail($id)->delete();
    }

    public function batal(): void
    {
        $this->resetForm();
    }

    // ── Bukti per komponen (foto/PDF) ─────────────────────────────────────────

    public function unggahBukti(LampiranService $lampiran, int $rincianId): void
    {
        $this->authorize('perjalanan-dinas');
        $this->validate(['bukti' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120']);

        $rincian = $this->suratTugas->rincian()->findOrFail($rincianId);
        $lampiran->simpan($rincian, $this->bukti, KategoriLampiran::BuktiPd);

        $this->reset('bukti');
    }

    public function hapusBukti(LampiranService $lampiran, int $lampiranId): void
    {
        $this->authorize('perjalanan-dinas');
        $lampiran->hapus(Lampiran::findOrFail($lampiranId));
    }

    // ── Uang muka (tunai/transfer) ────────────────────────────────────────────

    public function catatUangMuka(UangMukaPdService $service): void
    {
        $this->authorize('perjalanan-dinas');
        $this->validate([
            'umPegawaiIndex' => 'required|integer|min:0',
            'umTanggal' => 'required|date',
            'umJumlah' => 'required|integer|min:1',
            'umMetode' => 'required|in:'.implode(',', array_column(Sumber::cases(), 'value')),
            'umKeterangan' => 'nullable|string|max:255',
        ]);

        try {
            $service->catat($this->suratTugas, [
                'pegawai_index' => $this->umPegawaiIndex,
                'tanggal' => $this->umTanggal,
                'jumlah' => $this->umJumlah,
                'metode' => $this->umMetode,
                'keterangan' => $this->umKeterangan ?: null,
            ]);
        } catch (AuthorizationException $e) {
            $this->addError('umTanggal', $e->getMessage());

            return;
        }

        $this->reset(['umJumlah', 'umKeterangan']);
    }

    public function hapusUangMuka(int $id): void
    {
        $this->authorize('perjalanan-dinas');
        $this->suratTugas->uangMuka()->findOrFail($id)->delete();
    }

    public function render(RincianPdService $service, LampiranService $lampiran)
    {
        $rincian = $this->suratTugas->rincian()->with('lampiran')->orderBy('pegawai_index')->orderBy('urutan')->get();

        return view('livewire.perjalanan.kelola-rincian', [
            'daftarPegawai' => $this->suratTugas->pegawai ?? [],
            'rincianPerPegawai' => $rincian->groupBy('pegawai_index'),
            'ringkas' => $service->ringkas($this->suratTugas),
            'daftarUangMuka' => $this->suratTugas->uangMuka()->orderBy('urutan')->get(),
            'komponenPilihan' => KomponenBiaya::cases(),
            'pembayarPilihan' => PembayarPd::cases(),
            'metodePilihan' => Sumber::cases(),
            'urlBukti' => fn (Lampiran $l) => $lampiran->urlSementara($l),
        ]);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'uraian', 'harga_satuan', 'keterangan']);
        $this->harga_satuan = 0;
        $this->qty = in_array($this->komponen, [KomponenBiaya::UangHarian->value, KomponenBiaya::Fullboard->value], true)
            ? $this->hariDefault()
            : 1;
    }
}
