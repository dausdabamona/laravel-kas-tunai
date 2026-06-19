<?php

use App\Enums\JenisTransaksi;
use App\Enums\Sumber;
use App\Models\TransaksiKas;
use App\Services\SaldoService;

beforeEach(function () {
    config()->set('kas.saldo_awal_tunai', 0);
    config()->set('kas.saldo_awal_bank', 0);
    $this->saldo = app(SaldoService::class);
});

it('delta mengembalikan nol saat tidak ada transaksi', function () {
    expect($this->saldo->deltaPerSumber(Sumber::Tunai))->toBe(0)
        ->and($this->saldo->deltaPerSumber(Sumber::Bank))->toBe(0);
});

it('menghitung delta tunai dari debet minus kredit', function () {
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

    expect($this->saldo->deltaPerSumber(Sumber::Tunai))->toBe(3_500_000);
});

it('menghitung delta bank secara terpisah dari tunai', function () {
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

    expect($this->saldo->deltaPerSumber(Sumber::Bank))->toBe(10_000_000)
        ->and($this->saldo->deltaPerSumber(Sumber::Tunai))->toBe(2_000_000);
});

it('saldoPerSumber = saldo awal + delta', function () {
    config()->set('kas.saldo_awal_tunai', 3_218_000);
    config()->set('kas.saldo_awal_bank', 0);
    $saldo = app(SaldoService::class);

    TransaksiKas::factory()->create([
        'sumber' => Sumber::Tunai,
        'debet' => 1_000_000,
        'kredit' => 0,
        'jenis' => JenisTransaksi::Masuk,
    ]);

    // delta murni TIDAK termasuk saldo awal
    expect($saldo->deltaPerSumber(Sumber::Tunai))->toBe(1_000_000)
        // saldo riil = 3.218.000 + 1.000.000
        ->and($saldo->saldoPerSumber(Sumber::Tunai))->toBe(4_218_000)
        ->and($saldo->saldoPerSumber(Sumber::Bank))->toBe(0);
});

it('rekap memakai saldo riil (awal + delta) dan menjumlahkan total', function () {
    config()->set('kas.saldo_awal_tunai', 1_000_000);
    config()->set('kas.saldo_awal_bank', 500_000);
    $saldo = app(SaldoService::class);

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

    $rekap = $saldo->rekap();

    expect($rekap['tunai'])->toBe(3_500_000)   // 1jt + (3jt-500rb)
        ->and($rekap['bank'])->toBe(7_500_000) // 500rb + 7jt
        ->and($rekap['total'])->toBe(11_000_000);
});

it('pindah dana (ref_group sama, kredit asal + debet tujuan) tidak mengubah saldo total', function () {
    config()->set('kas.saldo_awal_tunai', 5_000_000);
    config()->set('kas.saldo_awal_bank', 5_000_000);
    $saldo = app(SaldoService::class);

    $totalSebelum = $saldo->rekap()['total'];

    // Tarik 2jt dari bank ke tunai — net antar kas = nol
    TransaksiKas::factory()->create([
        'sumber' => Sumber::Bank,
        'debet' => 0,
        'kredit' => 2_000_000,
        'jenis' => JenisTransaksi::PindahDana,
        'ref_group' => 'TF-001',
    ]);
    TransaksiKas::factory()->create([
        'sumber' => Sumber::Tunai,
        'debet' => 2_000_000,
        'kredit' => 0,
        'jenis' => JenisTransaksi::PindahDana,
        'ref_group' => 'TF-001',
    ]);

    $rekap = $saldo->rekap();

    expect($rekap['total'])->toBe($totalSebelum)        // total tak berubah
        ->and($rekap['bank'])->toBe(3_000_000)          // 5jt - 2jt
        ->and($rekap['tunai'])->toBe(7_000_000);        // 5jt + 2jt
});

it('mengabaikan transaksi yang telah dihapus lunak', function () {
    $trx = TransaksiKas::factory()->create([
        'sumber' => Sumber::Tunai,
        'debet' => 5_000_000,
        'kredit' => 0,
        'jenis' => JenisTransaksi::Masuk,
    ]);
    $trx->delete();

    expect($this->saldo->deltaPerSumber(Sumber::Tunai))->toBe(0);
});

it('delta bisa negatif (misal kredit melebihi debet)', function () {
    TransaksiKas::factory()->create([
        'sumber' => Sumber::Tunai,
        'debet' => 0,
        'kredit' => 1_000_000,
        'jenis' => JenisTransaksi::Belanja,
    ]);

    expect($this->saldo->deltaPerSumber(Sumber::Tunai))->toBe(-1_000_000);
});
