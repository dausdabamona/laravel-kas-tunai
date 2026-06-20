<?php

namespace App\Http\Controllers;

use App\Enums\Sumber;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cetak laporan kas (BKU per sumber, LPJ). Blade + window.print, kop config/satker.
 */
class LaporanController extends Controller
{
    public function __construct(private LaporanService $laporan) {}

    public function bku(Sumber $sumber, Request $request): View
    {
        $this->authorize('lihat-laporan');

        [$dari, $sampai] = $this->rentang($request);

        return view('cetak.laporan.bku', [
            'sumber' => $sumber,
            'baris' => $this->laporan->bku($sumber, $dari, $sampai),
            'saldoAwal' => $this->laporan->saldoAwalPeriode($sumber, $dari),
            'dari' => $dari,
            'sampai' => $sampai,
        ]);
    }

    public function lpj(Request $request): View
    {
        $this->authorize('lihat-laporan');

        [$dari, $sampai] = $this->rentang($request);

        return view('cetak.laporan.lpj', [
            'rekap' => $this->laporan->rekap($dari, $sampai),
            'dari' => $dari,
            'sampai' => $sampai,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function rentang(Request $request): array
    {
        return [
            $request->query('dari', now()->startOfMonth()->format('Y-m-d')),
            $request->query('sampai', now()->endOfMonth()->format('Y-m-d')),
        ];
    }
}
