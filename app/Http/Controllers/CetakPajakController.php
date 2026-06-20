<?php

namespace App\Http\Controllers;

use App\Models\MultiNota;
use App\Services\PajakService;
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

    /**
     * Susun konteks pajak dari nota: klasifikasi uraian transaksi + hitung().
     *
     * @return array{nota: MultiNota, hitung: array<string, mixed>}
     */
    private function dataPajak(MultiNota $nota): array
    {
        $uraian = $nota->transaksi?->kegiatan ?? '';
        $kategori = $this->pajak->klasifikasi($uraian);
        $hitung = $this->pajak->hitung($nota->nominal, $kategori, filled($nota->npwp_penyedia));

        return ['nota' => $nota, 'hitung' => $hitung];
    }
}
