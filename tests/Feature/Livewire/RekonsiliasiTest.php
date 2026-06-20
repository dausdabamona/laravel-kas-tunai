<?php

use App\Enums\JenisTransaksi;
use App\Enums\Role;
use App\Enums\StatusSpj;
use App\Enums\Sumber;
use App\Livewire\Rekonsiliasi;
use App\Models\MultiNota;
use App\Models\Pengembalian;
use App\Models\Tambahan;
use App\Models\TransaksiKas;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('kas.saldo_awal_tunai', 0);
    config()->set('kas.saldo_awal_bank', 0);
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
});

function belanjaRekon(array $attr = []): TransaksiKas
{
    return TransaksiKas::factory()->create(array_merge([
        'jenis' => JenisTransaksi::Belanja,
        'sumber' => Sumber::Tunai,
        'debet' => 0,
        'kredit' => 1_000_000,
        'nilai_spby' => 0,
        'uang_diserahkan' => 0,
        'status_spj' => StatusSpj::Belum,
        'kegiatan' => 'Belanja ATK',
    ], $attr));
}

it('komponen rekonsiliasi dapat dirender', function () {
    $trx = belanjaRekon();

    Livewire::test(Rekonsiliasi::class, ['transaksi' => $trx])
        ->assertStatus(200)
        ->assertSee('Rekonsiliasi Uang Muka');
});

it('catat pengembalian lewat komponen membuat record', function () {
    $trx = belanjaRekon(['kredit' => 1_000_000]);
    MultiNota::factory()->create(['transaksi_id' => $trx->id, 'nominal' => 700_000]);

    Livewire::test(Rekonsiliasi::class, ['transaksi' => $trx])
        ->set('tglPengembalian', '2026-06-19')
        ->set('jumlahPengembalian', 300_000)
        ->call('catatPengembalian')
        ->assertDispatched('rekonsiliasi-tersimpan');

    expect((int) Pengembalian::where('transaksi_id', $trx->id)->sum('jumlah'))->toBe(300_000);
});

it('catat tambahan lewat komponen membuat record', function () {
    $trx = belanjaRekon(['kredit' => 1_000_000]);
    MultiNota::factory()->create(['transaksi_id' => $trx->id, 'nominal' => 1_200_000]);

    Livewire::test(Rekonsiliasi::class, ['transaksi' => $trx])
        ->set('tglTambahan', '2026-06-19')
        ->set('jumlahTambahan', 200_000)
        ->call('catatTambahan')
        ->assertDispatched('rekonsiliasi-tersimpan');

    expect((int) Tambahan::where('transaksi_id', $trx->id)->sum('jumlah'))->toBe(200_000);
});

it('isi otomatis mengisi jumlah sebesar selisih', function () {
    $trx = belanjaRekon(['kredit' => 1_000_000]);
    MultiNota::factory()->create(['transaksi_id' => $trx->id, 'nominal' => 700_000]);

    Livewire::test(Rekonsiliasi::class, ['transaksi' => $trx])
        ->call('isiOtomatis', 'pengembalian')
        ->assertSet('jumlahPengembalian', 300_000);
});

it('menolak jumlah nol', function () {
    $trx = belanjaRekon();

    Livewire::test(Rekonsiliasi::class, ['transaksi' => $trx])
        ->set('tglPengembalian', '2026-06-19')
        ->set('jumlahPengembalian', 0)
        ->call('catatPengembalian')
        ->assertHasErrors(['jumlahPengembalian']);
});

// GUARD: uang muka == nota -> tidak ada sisa, pengembalian ditolak.
it('menolak pengembalian saat uang muka sama dengan nota (tidak ada sisa)', function () {
    $trx = belanjaRekon(['kredit' => 1_500_000]);
    MultiNota::factory()->create(['transaksi_id' => $trx->id, 'nominal' => 1_500_000]);

    Livewire::test(Rekonsiliasi::class, ['transaksi' => $trx])
        ->set('tglPengembalian', '2026-06-19')
        ->set('jumlahPengembalian', 300_000)
        ->call('catatPengembalian')
        ->assertHasErrors(['jumlahPengembalian']);

    expect(Pengembalian::where('transaksi_id', $trx->id)->count())->toBe(0);
});

it('menolak pengembalian melebihi sisa', function () {
    $trx = belanjaRekon(['kredit' => 1_000_000]);
    MultiNota::factory()->create(['transaksi_id' => $trx->id, 'nominal' => 700_000]); // sisa 300rb

    Livewire::test(Rekonsiliasi::class, ['transaksi' => $trx])
        ->set('tglPengembalian', '2026-06-19')
        ->set('jumlahPengembalian', 500_000)
        ->call('catatPengembalian')
        ->assertHasErrors(['jumlahPengembalian']);

    expect(Pengembalian::where('transaksi_id', $trx->id)->count())->toBe(0);
});

it('menolak tambahan saat tidak ada kekurangan (nota belum melebihi uang muka)', function () {
    $trx = belanjaRekon(['kredit' => 1_500_000]);
    MultiNota::factory()->create(['transaksi_id' => $trx->id, 'nominal' => 1_500_000]);

    Livewire::test(Rekonsiliasi::class, ['transaksi' => $trx])
        ->set('tglTambahan', '2026-06-19')
        ->set('jumlahTambahan', 100_000)
        ->call('catatTambahan')
        ->assertHasErrors(['jumlahTambahan']);

    expect(Tambahan::where('transaksi_id', $trx->id)->count())->toBe(0);
});
