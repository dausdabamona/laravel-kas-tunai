<?php

use App\Http\Controllers\CetakPajakController;
use App\Http\Controllers\CetakPerjalananController;
use App\Http\Controllers\LampiranController;
use App\Http\Controllers\LaporanController;
use App\Models\TransaksiKas;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// ── Transaksi Kas ────────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('transaksi-kas')->name('transaksi-kas.')->group(function () {
    Route::view('/', 'transaksi-kas.index')->name('index');
    Route::view('/tambah', 'transaksi-kas.create')->name('create');
    Route::get('/ubah/{transaksi}', function (TransaksiKas $transaksi) {
        return view('transaksi-kas.edit', compact('transaksi'));
    })->name('edit');
});

// ── Lampiran (stream privat via signed route) ────────────────────────────────
Route::get('/lampiran/{lampiran}/stream', [LampiranController::class, 'stream'])
    ->middleware(['auth', 'signed'])
    ->name('lampiran.stream');

Route::get('/transaksi/{transaksi}/bukti-zip', [LampiranController::class, 'zipBukti'])
    ->middleware(['auth', 'signed'])
    ->name('lampiran.bukti-zip');

// ── Laporan (BKU/LPJ) ────────────────────────────────────────────────────────
Route::middleware('auth')->prefix('laporan')->name('laporan.')->group(function () {
    Route::get('bku/{sumber}', [LaporanController::class, 'bku'])->name('bku');
    Route::get('lpj', [LaporanController::class, 'lpj'])->name('lpj');
});

// ── Cetak Pajak (SSP/Kuitansi/SPJ) ───────────────────────────────────────────
Route::middleware('auth')->prefix('cetak/pajak')->name('cetak.pajak.')->group(function () {
    Route::get('ssp-pph/{nota}', [CetakPajakController::class, 'sspPph'])->name('ssp-pph');
    Route::get('ssp-ppn/{nota}', [CetakPajakController::class, 'sspPpn'])->name('ssp-ppn');
    Route::get('kuitansi/{nota}', [CetakPajakController::class, 'kuitansi'])->name('kuitansi');
    Route::get('spj/{transaksi}', [CetakPajakController::class, 'spj'])->name('spj');
});

// ── Cetak Perjalanan Dinas (Blade + window.print) ────────────────────────────
Route::middleware('auth')->prefix('cetak/perjalanan/{suratTugas}')->name('cetak.pd.')->group(function () {
    Route::get('surat-tugas', [CetakPerjalananController::class, 'suratTugas'])->name('surat-tugas');
    Route::get('rincian', [CetakPerjalananController::class, 'rincian'])->name('rincian');
    Route::get('spd', [CetakPerjalananController::class, 'spd'])->name('spd');
    Route::get('pengesahan', [CetakPerjalananController::class, 'pengesahan'])->name('pengesahan');
    Route::get('pengeluaran-riil', [CetakPerjalananController::class, 'pengeluaranRiil'])->name('pengeluaran-riil');
});

require __DIR__.'/auth.php';
