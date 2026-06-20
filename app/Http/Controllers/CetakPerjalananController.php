<?php

namespace App\Http\Controllers;

use App\Models\SuratTugas;
use App\Services\RincianPdService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Render dokumen cetak perjalanan dinas (Blade + window.print sisi klien).
 * Tanpa lib PDF server. Identitas institusi diambil dari config/satker.
 */
class CetakPerjalananController extends Controller
{
    public function __construct(private RincianPdService $rincianPd) {}

    public function suratTugas(SuratTugas $suratTugas): View
    {
        $this->authorize('lihat-laporan');

        return view('cetak.perjalanan.surat-tugas', ['st' => $suratTugas]);
    }

    /**
     * Rincian Biaya PD per pegawai (?pegawai=indeks). Berbasis data rincian_pd;
     * bila pegawai belum punya rincian, dokumen tampil kosong (silakan isi dulu).
     */
    public function rincian(SuratTugas $suratTugas, Request $request): View
    {
        $this->authorize('lihat-laporan');

        $idx = (int) $request->query('pegawai', 0);

        return view('cetak.perjalanan.rincian', [
            'st' => $suratTugas,
            'pegawaiIndex' => $idx,
            'data' => $this->rincianPd->ringkasPegawai($suratTugas, $idx),
        ]);
    }

    public function kuitansi(SuratTugas $suratTugas, Request $request): View
    {
        $this->authorize('lihat-laporan');

        $idx = (int) $request->query('pegawai', 0);

        return view('cetak.perjalanan.kuitansi', [
            'st' => $suratTugas,
            'pegawaiIndex' => $idx,
            'data' => $this->rincianPd->ringkasPegawai($suratTugas, $idx),
        ]);
    }

    public function spd(SuratTugas $suratTugas): View
    {
        $this->authorize('lihat-laporan');

        return view('cetak.perjalanan.spd', ['st' => $suratTugas]);
    }

    public function pengesahan(SuratTugas $suratTugas): View
    {
        $this->authorize('lihat-laporan');

        return view('cetak.perjalanan.pengesahan', ['st' => $suratTugas]);
    }

    public function pengeluaranRiil(SuratTugas $suratTugas): View
    {
        $this->authorize('lihat-laporan');

        return view('cetak.perjalanan.pengeluaran-riil', ['st' => $suratTugas]);
    }
}
