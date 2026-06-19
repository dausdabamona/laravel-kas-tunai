<?php

use App\Enums\JenisTransaksi;
use App\Enums\Sumber;
use App\Models\TransaksiKas;
use App\Services\SaldoService;

beforeEach(function () {
    $this->saldo = app(SaldoService::class);
});

it('mengembalikan nol saat tidak ada transaksi', function () {
    expect($this->saldo->perSumber(Sumber::Tunai))->toBe(0)
        ->and($this->saldo->perSumber(Sumber::Bank))->toBe(0);
});

it('menghitung saldo tunai dari debet minus kredit', function () {
    TransaksiKas::factory()->create([
        'sumber' => Sumber::Tunai,
        'debet' => 5_000_000,
        'kredit' => 0,
        'jenis' => JenisTransaksi::Masuk,
    ]);
    TransaksiKas::factory()->create([
        'sumber' => Sumber::Tunai,
        'debet' => 0,
        'kredit' => 1_500_000,
        'jenis' => JenisTransaksi::Belanja,
    ]);

    expect($this->saldo->perSumber(Sumber::Tunai))->toBe(3_500_000);
});

it('menghitung saldo bank secara terpisah dari tunai', function () {
    TransaksiKas::factory()->create([
        'sumber' => Sumber::Bank,
        'debet' => 10_000_000,
        'kredit' => 0,
        'jenis' => JenisTransaksi::Masuk,
    ]);
    TransaksiKas::factory()->create([
        'sumber' => Sumber::Tunai,
        'debet' => 2_000_000,
        'kredit' => 0,
        'jenis' => JenisTransaksi::Masuk,
    ]);

    expect($this->saldo->perSumber(Sumber::Bank))->toBe(10_000_000)
        ->and($this->saldo->perSumber(Sumber::Tunai))->toBe(2_000_000);
});

it('rekap menggabungkan kedua sumber', function () {
    TransaksiKas::factory()->create([
        'sumber' => Sumber::Tunai,
        'debet' => 3_000_000,
        'kredit' => 500_000,
        'jenis' => JenisTransaksi::Masuk,
    ]);
    TransaksiKas::factory()->create([
        'sumber' => Sumber::Bank,
        'debet' => 7_000_000,
        'kredit' => 0,
        'jenis' => JenisTransaksi::Masuk,
    ]);

    $rekap = $this->saldo->rekap();

    expect($rekap['tunai'])->toBe(2_500_000)
        ->and($rekap['bank'])->toBe(7_000_000)
        ->and($rekap['total'])->toBe(9_500_000);
});

it('mengabaikan transaksi yang telah dihapus lunak', function () {
    $trx = TransaksiKas::factory()->create([
        'sumber' => Sumber::Tunai,
        'debet' => 5_000_000,
        'kredit' => 0,
        'jenis' => JenisTransaksi::Masuk,
    ]);
    $trx->delete();

    expect($this->saldo->perSumber(Sumber::Tunai))->toBe(0);
});

it('saldo bisa negatif (misal kredit melebihi debet)', function () {
    TransaksiKas::factory()->create([
        'sumber' => Sumber::Tunai,
        'debet' => 0,
        'kredit' => 1_000_000,
        'jenis' => JenisTransaksi::Belanja,
    ]);

    expect($this->saldo->perSumber(Sumber::Tunai))->toBe(-1_000_000);
});
