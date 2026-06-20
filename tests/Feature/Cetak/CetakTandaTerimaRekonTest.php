<?php

use App\Enums\JenisTransaksi;
use App\Enums\Role;
use App\Enums\Sumber;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Services\PengembalianService;
use App\Services\TambahanService;

beforeEach(function () {
    config()->set('kas.periode_terkunci_hingga', null);
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
});

function trxBelanjaTT(): TransaksiKas
{
    return TransaksiKas::factory()->create([
        'jenis' => JenisTransaksi::Belanja,
        'sumber' => Sumber::Tunai,
        'debet' => 0,
        'kredit' => 1_000_000,
        'penjab' => 'Budi Santoso',
        'kegiatan' => 'Belanja ATK',
    ]);
}

it('cetak tanda terima pengembalian: jumlah, terbilang & penyerah muncul', function () {
    $trx = trxBelanjaTT();
    $p = app(PengembalianService::class)->catat($trx, ['tanggal' => '2026-06-19', 'jumlah' => 300_000, 'keterangan' => 'sisa']);

    $this->get(route('cetak.pajak.tt-pengembalian', $p))
        ->assertOk()
        ->assertSee('Tanda Terima Pengembalian')
        ->assertSee('300.000')
        ->assertSee('Budi Santoso');
});

it('cetak tanda terima penambahan: jumlah & penerima muncul', function () {
    $trx = trxBelanjaTT();
    $t = app(TambahanService::class)->catat($trx, ['tanggal' => '2026-06-19', 'jumlah' => 200_000]);

    $this->get(route('cetak.pajak.tt-tambahan', $t))
        ->assertOk()
        ->assertSee('Tanda Terima Penambahan')
        ->assertSee('200.000')
        ->assertSee('Budi Santoso');
});
