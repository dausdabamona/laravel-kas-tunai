<?php

use App\Enums\JenisTransaksi;
use App\Enums\Sumber;
use App\Livewire\TransaksiKas\Index;
use App\Models\TransaksiKas;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('komponen dapat dirender', function () {
    Livewire::test(Index::class)->assertStatus(200);
});

it('menampilkan daftar transaksi yang ada', function () {
    $trx = TransaksiKas::factory()->create(['kegiatan' => 'Belanja Makan Rapat']);

    Livewire::test(Index::class)
        ->assertSee('Belanja Makan Rapat');
});

it('tidak menampilkan transaksi yang telah dihapus lunak', function () {
    $trx = TransaksiKas::factory()->create(['kegiatan' => 'Data Terhapus']);
    $trx->delete();

    Livewire::test(Index::class)
        ->assertDontSee('Data Terhapus');
});

it('dapat difilter berdasarkan sumber', function () {
    TransaksiKas::factory()->create(['sumber' => Sumber::Tunai, 'kegiatan' => 'Beli Tunai']);
    TransaksiKas::factory()->create(['sumber' => Sumber::Bank, 'kegiatan' => 'Transfer Bank']);

    Livewire::test(Index::class)
        ->set('filterSumber', Sumber::Tunai->value)
        ->assertSee('Beli Tunai')
        ->assertDontSee('Transfer Bank');
});

it('dapat mencari berdasarkan kegiatan', function () {
    TransaksiKas::factory()->create(['kegiatan' => 'Pembelian Proyektor']);
    TransaksiKas::factory()->create(['kegiatan' => 'Biaya Listrik Kantor']);

    Livewire::test(Index::class)
        ->set('cari', 'Proyektor')
        ->assertSee('Pembelian Proyektor')
        ->assertDontSee('Biaya Listrik Kantor');
});

it('dapat difilter berdasarkan rentang tanggal', function () {
    TransaksiKas::factory()->create(['tanggal' => '2026-06-01', 'kegiatan' => 'Juni Awal']);
    TransaksiKas::factory()->create(['tanggal' => '2026-06-30', 'kegiatan' => 'Juni Akhir']);
    TransaksiKas::factory()->create(['tanggal' => '2026-05-15', 'kegiatan' => 'Mei Lalu']);

    Livewire::test(Index::class)
        ->set('filterDariTanggal', '2026-06-01')
        ->set('filterSampaiTanggal', '2026-06-30')
        ->assertSee('Juni Awal')
        ->assertSee('Juni Akhir')
        ->assertDontSee('Mei Lalu');
});

it('menampilkan rekap saldo per sumber', function () {
    TransaksiKas::factory()->create([
        'sumber' => Sumber::Tunai,
        'debet' => 2_000_000,
        'kredit' => 0,
        'jenis' => JenisTransaksi::Masuk,
    ]);

    Livewire::test(Index::class)
        ->assertSet('rekap.tunai', 2_000_000);
});
