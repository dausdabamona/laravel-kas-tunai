<?php

namespace App\Http\Controllers;

use App\Models\MasterPenyedia;
use App\Models\MultiNota;
use App\Models\SuratTugas;
use App\Models\TransaksiKas;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalDebet = (int) TransaksiKas::sum('debet');
        $totalKredit = (int) TransaksiKas::sum('kredit');

        $summary = [
            'total_transaksi' => TransaksiKas::count(),
            'total_nota' => MultiNota::count(),
            'total_penyedia' => MasterPenyedia::count(),
            'total_perjalanan_dinas' => SuratTugas::count(),
            'total_debet' => $totalDebet,
            'total_kredit' => $totalKredit,
            'saldo' => $totalDebet - $totalKredit,
        ];

        $recentTransaksi = TransaksiKas::query()
            ->latest('tanggal')
            ->latest('id')
            ->take(8)
            ->get();

        $recentSuratTugas = SuratTugas::query()
            ->with('transaksi')
            ->latest('tgl_berangkat')
            ->latest('id')
            ->take(4)
            ->get();

        return view('dashboard', compact('summary', 'recentTransaksi', 'recentSuratTugas'));
    }
}
