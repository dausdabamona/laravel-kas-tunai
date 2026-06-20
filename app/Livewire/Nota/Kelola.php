<?php

namespace App\Livewire\Nota;

use App\Enums\KategoriLampiran;
use App\Models\Lampiran;
use App\Models\MasterPenyedia;
use App\Models\MultiNota;
use App\Models\TransaksiKas;
use App\Services\LampiranService;
use App\Services\PajakService;
use App\Services\PenyediaService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Kelola rincian nota + foto (nota & barang) untuk satu transaksi.
 *
 * Komponen tipis: delegasi ke PenyediaService (reuse penyedia, frekuensi++) &
 * LampiranService (storage privat + kompresi queue). recalc status_spj jalan
 * OTOMATIS via event MultiNota — komponen tidak memanggil recalc manual.
 */
class Kelola extends Component
{
    use WithFileUploads;

    public TransaksiKas $transaksi;

    public ?int $editingId = null;

    #[Validate('required|string|max:255')]
    public string $nama_penyedia = '';

    #[Validate('nullable|string|max:255')]
    public string $npwp_penyedia = '';

    #[Validate('nullable|string|max:255')]
    public string $alamat_penyedia = '';

    // Override kategori pajak; '' = otomatis dari uraian kegiatan transaksi.
    #[Validate('nullable|string|max:255')]
    public string $kategori_pajak = '';

    #[Validate('required|integer|min:1')]
    public int $nominal = 0;

    #[Validate('nullable|date')]
    public string $tgl_nota = '';

    /** @var array<int, array{id:int,nama:string,npwp:?string,alamat:?string}> */
    public array $kandidatPenyedia = [];

    // Upload + GPS (lat/lng diisi Alpine navigator.geolocation sebelum upload).
    public $fotoNota;

    public $fotoBarang;

    public ?float $lat = null;

    public ?float $lng = null;

    public ?string $maps_url = null;

    public function mount(TransaksiKas $transaksi): void
    {
        $this->transaksi = $transaksi;
    }

    public function cariPenyedia(PenyediaService $penyedia): void
    {
        $this->kandidatPenyedia = $penyedia->cari($this->nama_penyedia)
            ->map(fn (MasterPenyedia $p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'npwp' => $p->npwp,
                'alamat' => $p->alamat,
            ])
            ->all();
    }

    public function pilihPenyedia(int $id): void
    {
        $p = MasterPenyedia::findOrFail($id);
        $this->nama_penyedia = $p->nama;
        $this->npwp_penyedia = $p->npwp ?? '';
        $this->alamat_penyedia = $p->alamat ?? '';
        $this->kandidatPenyedia = [];
    }

    public function editNota(int $id): void
    {
        $nota = $this->transaksi->nota()->findOrFail($id);
        $this->editingId = $nota->id;
        $this->nama_penyedia = $nota->nama_penyedia;
        $this->npwp_penyedia = $nota->npwp_penyedia ?? '';
        $this->alamat_penyedia = $nota->alamat_penyedia ?? '';
        $this->kategori_pajak = $nota->kategori_pajak ?? '';
        $this->nominal = $nota->nominal;
        $this->tgl_nota = $nota->tgl_nota?->format('Y-m-d') ?? '';
    }

    public function simpanNota(PenyediaService $penyedia): void
    {
        $this->authorize('update', $this->transaksi);
        $data = $this->validate();

        DB::transaction(function () use ($penyedia, $data) {
            // Reuse penyedia (frekuensi++) — trigger yang ditunda sejak 2.0 mendarat di sini.
            $master = $penyedia->simpanAtauUpdate([
                'nama' => $data['nama_penyedia'],
                'npwp' => $data['npwp_penyedia'] ?: null,
                'alamat' => $data['alamat_penyedia'] ?: null,
            ]);

            $atribut = [
                'nama_penyedia' => $data['nama_penyedia'],
                'npwp_penyedia' => $data['npwp_penyedia'] ?: null,
                'alamat_penyedia' => $data['alamat_penyedia'] ?: null,
                'kategori_pajak' => $data['kategori_pajak'] ?: null,
                'nominal' => $data['nominal'],
                'tgl_nota' => $data['tgl_nota'] ?: null,
                'penyedia_id' => $master->id,
            ];

            if ($this->editingId) {
                $this->transaksi->nota()->findOrFail($this->editingId)->update($atribut);
            } else {
                $atribut['urutan'] = (int) $this->transaksi->nota()->max('urutan') + 1;
                $this->transaksi->nota()->create($atribut);
            }
            // recalc status_spj otomatis via event MultiNota (saved).
        });

        $this->resetForm();
        $this->dispatch('rincian-nota-berubah'); // refresh panel rekonsiliasi
    }

    public function hapusNota(int $id): void
    {
        $this->authorize('update', $this->transaksi);
        $this->transaksi->nota()->findOrFail($id)->delete();
        $this->dispatch('rincian-nota-berubah');
    }

    public function simpanFotoNota(LampiranService $lampiran, int $notaId): void
    {
        $this->authorize('update', $this->transaksi);
        $this->validate(['fotoNota' => 'required|image|max:5120']);

        $nota = $this->transaksi->nota()->findOrFail($notaId);
        $lampiran->simpan($nota, $this->fotoNota, KategoriLampiran::FotoNota, $this->metaGps());

        $this->reset('fotoNota');
    }

    public function simpanFotoBarang(LampiranService $lampiran, int $notaId): void
    {
        $this->authorize('update', $this->transaksi);
        $this->validate(['fotoBarang' => 'required|image|max:5120']);

        // Foto barang BERPASANGAN dengan nota — menempel ke nota yang sama.
        $nota = $this->transaksi->nota()->findOrFail($notaId);
        $lampiran->simpan($nota, $this->fotoBarang, KategoriLampiran::FotoBarang, $this->metaGps());

        $this->reset('fotoBarang');
    }

    public function hapusFoto(LampiranService $lampiran, int $id): void
    {
        $this->authorize('update', $this->transaksi);
        $lampiran->hapus(Lampiran::findOrFail($id));
    }

    /** Header transaksi berubah (kredit/uang muka) -> status SPJ bisa berubah. */
    #[On('transaksi-diperbarui')]
    public function segarkan(): void
    {
        // Re-render saja; render() membaca ulang status_spj transaksi terbaru.
    }

    public function render(LampiranService $lampiran, PajakService $pajak)
    {
        $this->transaksi->refresh();

        $nota = $this->transaksi->nota()->orderBy('urutan')->get();

        // Ringkasan pajak per nota (kategori efektif + DPP/PPN/PPh) untuk badge tabel.
        $pajakNota = $nota->mapWithKeys(fn (MultiNota $n) => [
            $n->id => $pajak->hitung($n->nominal, $pajak->kategoriUntukNota($n), filled($n->npwp_penyedia)),
        ]);

        return view('livewire.nota.kelola', [
            'daftarNota' => $nota,
            'totalNota' => (int) $this->transaksi->nota()->sum('nominal'),
            'statusSpj' => $this->transaksi->status_spj,
            'urlFoto' => fn ($l) => $lampiran->urlSementara($l),
            'daftarKategori' => $pajak->daftarLabel(),
            'pajakNota' => $pajakNota,
        ]);
    }

    private function metaGps(): array
    {
        return array_filter([
            'lat' => $this->lat,
            'lng' => $this->lng,
            'maps_url' => $this->maps_url,
        ], fn ($v) => $v !== null);
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'nama_penyedia', 'npwp_penyedia', 'alamat_penyedia', 'kategori_pajak', 'nominal', 'tgl_nota', 'kandidatPenyedia']);
    }
}
