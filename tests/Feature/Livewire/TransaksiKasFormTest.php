<?php

use App\Enums\JenisTransaksi;
use App\Enums\StatusSpj;
use App\Enums\Sumber;
use App\Livewire\TransaksiKas\Form;
use App\Models\TransaksiKas;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('komponen form dapat dirender untuk buat baru', function () {
    Livewire::test(Form::class)->assertStatus(200);
});

it('menyimpan transaksi baru dengan data valid', function () {
    Livewire::test(Form::class)
        ->set('tanggal', '2026-06-19')
        ->set('kegiatan', 'Pembelian Alat Tulis Kantor')
        ->set('penjab', 'Budi Santoso')
        ->set('sumber', Sumber::Tunai->value)
        ->set('jenis', JenisTransaksi::Belanja->value)
        ->set('debet', 0)
        ->set('kredit', 150_000)
        ->set('status_spj', StatusSpj::Belum->value)
        ->call('simpan')
        ->assertDispatched('transaksi-tersimpan');

    expect(TransaksiKas::count())->toBe(1)
        ->and(TransaksiKas::first()->kegiatan)->toBe('Pembelian Alat Tulis Kantor');
});

it('jenis pengeluaran (belanja) menol-kan debet otomatis (cegah salah input)', function () {
    Livewire::test(Form::class)
        ->set('tanggal', '2026-06-19')
        ->set('kegiatan', 'Belanja ATK')
        ->set('sumber', Sumber::Tunai->value)
        ->set('jenis', JenisTransaksi::Belanja->value)
        ->set('debet', 999_999)   // salah isi — harus dinolkan
        ->set('kredit', 500_000)
        ->call('simpan')
        ->assertDispatched('transaksi-tersimpan');

    $trx = TransaksiKas::first();
    expect($trx->debet)->toBe(0)
        ->and($trx->kredit)->toBe(500_000);
});

it('jenis penerimaan (masuk) menol-kan kredit otomatis', function () {
    Livewire::test(Form::class)
        ->set('tanggal', '2026-06-19')
        ->set('kegiatan', 'Penerimaan UP')
        ->set('sumber', Sumber::Bank->value)
        ->set('jenis', JenisTransaksi::Masuk->value)
        ->set('kredit', 999_999)  // salah isi — harus dinolkan
        ->set('debet', 700_000)
        ->call('simpan')
        ->assertDispatched('transaksi-tersimpan');

    $trx = TransaksiKas::first();
    expect($trx->kredit)->toBe(0)
        ->and($trx->debet)->toBe(700_000);
});

it('menolak penyimpanan jika tanggal kosong', function () {
    Livewire::test(Form::class)
        ->set('kegiatan', 'Tanpa Tanggal')
        ->set('sumber', Sumber::Tunai->value)
        ->set('jenis', JenisTransaksi::Belanja->value)
        ->set('kredit', 100_000)
        ->call('simpan')
        ->assertHasErrors(['tanggal']);
});

it('menolak penyimpanan jika kegiatan kosong', function () {
    Livewire::test(Form::class)
        ->set('tanggal', '2026-06-19')
        ->set('sumber', Sumber::Tunai->value)
        ->set('jenis', JenisTransaksi::Belanja->value)
        ->set('kredit', 100_000)
        ->call('simpan')
        ->assertHasErrors(['kegiatan']);
});

it('menolak jika debet dan kredit keduanya nol', function () {
    Livewire::test(Form::class)
        ->set('tanggal', '2026-06-19')
        ->set('kegiatan', 'Test Nol')
        ->set('sumber', Sumber::Tunai->value)
        ->set('jenis', JenisTransaksi::Belanja->value)
        ->set('debet', 0)
        ->set('kredit', 0)
        ->call('simpan')
        ->assertHasErrors(['kredit']);
});

it('memuat data transaksi yang ada saat mode edit', function () {
    $trx = TransaksiKas::factory()->create([
        'kegiatan' => 'Data Lama Untuk Diedit',
        'sumber' => Sumber::Bank,
    ]);

    Livewire::test(Form::class, ['transaksiId' => $trx->id])
        ->assertSet('kegiatan', 'Data Lama Untuk Diedit')
        ->assertSet('sumber', Sumber::Bank->value);
});

it('memperbarui transaksi yang sudah ada saat mode edit', function () {
    $trx = TransaksiKas::factory()->create([
        'kegiatan' => 'Kegiatan Lama',
        'kredit' => 100_000,
        'status_spj' => StatusSpj::Belum,
    ]);

    Livewire::test(Form::class, ['transaksiId' => $trx->id])
        ->set('kegiatan', 'Kegiatan Baru Sudah Diperbarui')
        ->call('simpan')
        ->assertDispatched('transaksi-diperbarui') // panel sibling refresh tanpa reload
        ->assertSet('tersimpan', true);

    expect($trx->fresh()->kegiatan)->toBe('Kegiatan Baru Sudah Diperbarui');
});
