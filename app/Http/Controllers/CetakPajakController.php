<?php

namespace App\Http\Controllers;

use App\Enums\KategoriLampiran;
use App\Models\Lampiran;
use App\Models\MultiNota;
use App\Models\Pengembalian;
use App\Models\Tambahan;
use App\Models\TransaksiKas;
use App\Services\LampiranService;
use App\Services\PajakService;
use App\Services\RekonsiliasiService;
use Illuminate\View\View;

/**
 * Cetak dokumen pajak & pertanggungjawaban (Blade + window.print).
 * Data: MultiNota (penyedia) + TransaksiKas + PajakService. Identitas dari
 * config/satker; kode billing dari config/pajak.
 */
class CetakPajakController extends Controller
{
    public function __construct(private PajakService $pajak) {}

    public function sspPph(MultiNota $nota): View
    {
        $this->authorize('lihat-laporan');

        return view('cetak.pajak.ssp-pph', $this->dataPajak($nota));
    }

    public function sspPpn(MultiNota $nota): View
    {
        $this->authorize('lihat-laporan');

        return view('cetak.pajak.ssp-ppn', $this->dataPajak($nota));
    }

    public function kuitansi(MultiNota $nota): View
    {
        $this->authorize('lihat-laporan');

        return view('cetak.pajak.kuitansi', ['nota' => $nota]);
    }

    public function spj(TransaksiKas $transaksi, LampiranService $lampiran): View
    {
        $this->authorize('lihat-laporan');

        $nota = $transaksi->nota()->orderBy('urutan')->get();
        $pajak = $nota->map(fn (MultiNota $n) => [
            'nota' => $n,
            'hitung' => $this->pajak->hitung(
                $n->nominal,
                $this->pajak->kategoriUntukNota($n),
                filled($n->npwp_penyedia),
            ),
        ]);

        // Foto nota & foto barang BERPASANGAN per nota (gambar ditanam base64).
        $fotoNota = $nota->mapWithKeys(fn (MultiNota $n) => [
            $n->id => $n->lampiran()->where('kategori', KategoriLampiran::FotoNota->value)->orderBy('urutan')->get(),
        ]);

        $fotoBarang = $nota->mapWithKeys(fn (MultiNota $n) => [
            $n->id => $n->lampiran()->where('kategori', KategoriLampiran::FotoBarang->value)->orderBy('urutan')->get(),
        ]);

        return view('cetak.pajak.spj', [
            'transaksi' => $transaksi,
            'daftarNota' => $nota,
            'totalNota' => (int) $transaksi->nota()->sum('nominal'),
            'pajak' => $pajak,
            'fotoNota' => $fotoNota,
            'fotoBarang' => $fotoBarang,
            'dataUri' => fn (Lampiran $l) => $lampiran->dataUri($l),
            'rekon' => app(RekonsiliasiService::class)->untuk($transaksi),
            'daftarPengembalian' => $transaksi->pengembalian()->orderBy('urutan')->get(),
            'daftarTambahan' => $transaksi->tambahan()->orderBy('urutan')->get(),
        ]);
    }

    /**
     * Tanda Terima Pengembalian — bukti bendahara menerima sisa uang muka dari pelaksana.
     */
    public function tandaTerimaPengembalian(Pengembalian $pengembalian): View
    {
        $this->authorize('lihat-laporan');

        return view('cetak.pajak.tt-pengembalian', ['p' => $pengembalian, 'transaksi' => $pengembalian->transaksi]);
    }

    /**
     * Tanda Terima Penambahan — bukti pelaksana menerima tambahan kekurangan dari bendahara.
     */
    public function tandaTerimaTambahan(Tambahan $tambahan): View
    {
        $this->authorize('lihat-laporan');

        return view('cetak.pajak.tt-tambahan', ['t' => $tambahan, 'transaksi' => $tambahan->transaksi]);
    }

    /**
     * Tanda Terima Uang Muka Kerja — bukti penyerahan panjar ke pelaksana.
     * Nominal = kredit transaksi (kas yang keluar = uang muka diserahkan).
     */
    public function tandaTerima(TransaksiKas $transaksi): View
    {
        $this->authorize('lihat-laporan');

        $uangMuka = (int) $transaksi->kredit;

        return view('cetak.pajak.tanda-terima', [
            'transaksi' => $transaksi,
            'uangMuka' => $uangMuka,
        ]);
    }

    /**
     * Susun konteks pajak dari nota: klasifikasi uraian transaksi + hitung().
     *
     * @return array{nota: MultiNota, hitung: array<string, mixed>}
     */
    private function dataPajak(MultiNota $nota): array
    {
        $kategori = $this->pajak->kategoriUntukNota($nota);
        $hitung = $this->pajak->hitung($nota->nominal, $kategori, filled($nota->npwp_penyedia));

        return ['nota' => $nota, 'hitung' => $hitung];
    }
}
