<?php

namespace App\Services;

use App\Enums\KomponenBiaya;
use App\Enums\PembayarPd;
use App\Enums\Sumber;
use App\Models\RincianPd;
use App\Models\SuratTugas;
use Illuminate\Support\Collection;

/**
 * Read-model rincian biaya perjalanan dinas (tidak mempersist apa pun).
 *
 * Menyusun angka untuk kuitansi rampung & panel kelola:
 *  - total            : Σ seluruh komponen.
 *  - per_metode       : Σ per kas (tunai/bank) — basis dampak saldo.
 *  - per_pembayar     : Σ per pihak (bendahara/pelaksana) — narasi kuitansi.
 *  - bucket           : Σ per (pembayar × metode) — baris rekap dokumen
 *    ("Dibayar langsung Bendahara — Kas Bank", "Dibayarkan ke Pelaksana — Kas Tunai").
 *  - telah_dibayar    : Σ kredit baris kas PD (PdPokok/PdBendahara) pada ref_group
 *    surat tugas — uang yang BENAR-BENAR keluar dari kas (memengaruhi saldo).
 *  - sisa_kurang      : total − telah_dibayar (positif = masih kurang dibayar).
 */
class RincianPdService
{
    /**
     * @return array{
     *     total:int, per_metode:array{tunai:int,bank:int},
     *     per_pembayar:array{bendahara:int,pelaksana:int},
     *     bucket:array<string,int>, telah_dibayar:int, sisa_kurang:int
     * }
     */
    public function ringkas(SuratTugas $st): array
    {
        return $this->dari($st->rincian()->get(), $this->telahDibayar($st));
    }

    /**
     * Ringkasan untuk SATU pegawai (dipakai dokumen Rincian Biaya per pelaksana).
     * telah_dibayar default 0 — kas tidak terlacak per pegawai; isi saat pembayaran.
     *
     * @return array{nama:string, items:Collection<int,RincianPd>, ringkas:array}
     */
    public function ringkasPegawai(SuratTugas $st, int $pegawaiIndex): array
    {
        $items = $st->rincian()
            ->where('pegawai_index', $pegawaiIndex)
            ->orderBy('urutan')->get();

        // Fallback data lama: bila pegawai belum punya rincian_pd, susun dari JSON
        // pegawai (uang_harian × hari, transport, penginapan) agar dokumen tetap
        // terisi. Item ini TIDAK dipersist.
        if ($items->isEmpty()) {
            $items = $this->dariJson($st, $pegawaiIndex);
        }

        // Telah dibayar = Σ uang muka pegawai tsb.
        $telahDibayar = (int) $st->uangMuka()->where('pegawai_index', $pegawaiIndex)->sum('jumlah');

        return [
            'nama' => $items->first()?->pegawai_nama ?? ($st->pegawai[$pegawaiIndex]['nama'] ?? ''),
            'items' => $items,
            'ringkas' => $this->dari($items, $telahDibayar),
        ];
    }

    /**
     * Susun item rincian (tak dipersist) dari JSON pegawai surat tugas lama.
     *
     * @return Collection<int, RincianPd>
     */
    private function dariJson(SuratTugas $st, int $pegawaiIndex): Collection
    {
        $p = $st->pegawai[$pegawaiIndex] ?? null;

        if (! $p) {
            return collect();
        }

        $nama = $p['nama'] ?? '';
        $hari = (int) $st->lama_hari;
        $items = collect();
        $urut = 1;

        $tambah = function (KomponenBiaya $k, int $qty, int $harga, PembayarPd $pb, Sumber $mt, ?string $uraian = null) use (&$items, &$urut, $st, $pegawaiIndex, $nama) {
            if ($qty * $harga <= 0) {
                return;
            }
            $items->push(new RincianPd([
                'surat_tugas_id' => $st->id,
                'pegawai_index' => $pegawaiIndex,
                'pegawai_nama' => $nama,
                'komponen' => $k,
                'uraian' => $uraian,
                'qty' => $qty,
                'harga_satuan' => $harga,
                'nominal' => $qty * $harga,
                'pembayar' => $pb,
                'metode' => $mt,
                'urutan' => $urut++,
            ]));
        };

        $tambah(KomponenBiaya::UangHarian, $hari, (int) ($p['uang_harian'] ?? 0), PembayarPd::Pelaksana, Sumber::Tunai, 'Lumpsum');
        $tambah(KomponenBiaya::TransportUdara, 1, (int) ($p['transport'] ?? 0), PembayarPd::Bendahara, Sumber::Bank);
        $tambah(KomponenBiaya::Penginapan, 1, (int) ($p['penginapan'] ?? 0), PembayarPd::Bendahara, Sumber::Bank);

        return $items;
    }

    /**
     * @param  Collection<int, RincianPd>  $rincian
     */
    private function dari(Collection $rincian, int $telahDibayar): array
    {
        $total = (int) $rincian->sum('nominal');

        $bucket = [];
        foreach ($rincian as $r) {
            $key = $r->pembayar->value.'|'.$r->metode->value;
            $bucket[$key] = ($bucket[$key] ?? 0) + (int) $r->nominal;
        }

        return [
            'total' => $total,
            'per_metode' => [
                'tunai' => (int) $rincian->where('metode', Sumber::Tunai)->sum('nominal'),
                'bank' => (int) $rincian->where('metode', Sumber::Bank)->sum('nominal'),
            ],
            'per_pembayar' => [
                'bendahara' => (int) $rincian->where('pembayar', PembayarPd::Bendahara)->sum('nominal'),
                'pelaksana' => (int) $rincian->where('pembayar', PembayarPd::Pelaksana)->sum('nominal'),
            ],
            'bucket' => $bucket,
            'telah_dibayar' => $telahDibayar,
            'sisa_kurang' => $total - $telahDibayar,
        ];
    }

    /**
     * Uang yang sudah dibayarkan untuk PD ini = Σ uang muka (tunai/transfer) yang
     * dicatat. Tiap uang muka memunculkan baris kas PdUangMuka pada ref_group
     * surat tugas, jadi nilai ini konsisten dengan dampak saldo.
     */
    private function telahDibayar(SuratTugas $st): int
    {
        return (int) $st->uangMuka()->sum('jumlah');
    }
}
