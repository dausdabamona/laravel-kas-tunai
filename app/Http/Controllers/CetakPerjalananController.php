<?php

namespace App\Http\Controllers;

use App\Models\SuratTugas;
use Illuminate\View\View;

/**
 * Render dokumen cetak perjalanan dinas (Blade + window.print sisi klien).
 * Tanpa lib PDF server. Identitas institusi diambil dari config/satker.
 */
class CetakPerjalananController extends Controller
{
    public function suratTugas(SuratTugas $suratTugas): View
    {
        $this->authorize('lihat-laporan');

        return view('cetak.perjalanan.surat-tugas', ['st' => $suratTugas]);
    }

    public function rincian(SuratTugas $suratTugas): View
    {
        $this->authorize('lihat-laporan');

        return view('cetak.perjalanan.rincian', ['st' => $suratTugas]);
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
