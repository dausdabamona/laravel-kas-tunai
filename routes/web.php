<?php

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

require __DIR__.'/auth.php';
