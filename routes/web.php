<?php

use App\Http\Controllers\LampiranController;
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

require __DIR__.'/auth.php';
