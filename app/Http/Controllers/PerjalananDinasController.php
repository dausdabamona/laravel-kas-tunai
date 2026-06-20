<?php

namespace App\Http\Controllers;

use App\Models\SuratTugas;
use Illuminate\View\View;

class PerjalananDinasController extends Controller
{
    public function __invoke(): View
    {
        $suratTugas = SuratTugas::query()
            ->with('transaksi')
            ->latest('tgl_berangkat')
            ->latest('id')
            ->paginate(10);

        return view('perjalanan-dinas.index', compact('suratTugas'));
    }
}
