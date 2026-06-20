<?php

use App\Enums\KomponenBiaya;
use App\Enums\PembayarPd;
use App\Enums\Role;
use App\Enums\Sumber;
use App\Livewire\Perjalanan\KelolaRincian;
use App\Models\RincianPd;
use App\Models\SuratTugas;
use App\Models\User;
use App\Services\SuratTugasService;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('kas.periode_terkunci_hingga', null);
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
});

function stKelola(): SuratTugas
{
    return app(SuratTugasService::class)->simpan([
        'nomor_surat' => '099/ST/VI/2026',
        'maksud' => 'Koordinasi',
        'tempat_berangkat' => 'Sorong',
        'tempat_tujuan' => 'Ambon',
        'tgl_berangkat' => '2026-06-12',
        'tgl_kembali' => '2026-06-15',
        'lama_hari' => 4,
        'jenis' => 'luar_kota',
        'kegiatan' => 'PD Ambon',
        'pegawai' => [['nama' => 'Polly', 'nip' => '123', 'biaya' => 0]],
        'porsi_pelaksana' => 0,
        'sumber_pelaksana' => 'tunai',
        'porsi_bendahara' => 0,
        'sumber_bendahara' => null,
    ]);
}

it('komponen kelola rincian dapat dirender', function () {
    Livewire::test(KelolaRincian::class, ['suratTugas' => stKelola()])
        ->assertStatus(200)
        ->assertSee('Rincian Biaya Perjalanan Dinas');
});

it('tambah komponen: nominal = qty x harga tersimpan', function () {
    $st = stKelola();

    Livewire::test(KelolaRincian::class, ['suratTugas' => $st])
        ->set('pegawaiIndex', 0)
        ->set('komponen', KomponenBiaya::UangHarian->value)
        ->set('qty', 4)
        ->set('harga_satuan', 480_000)
        ->set('pembayar', PembayarPd::Pelaksana->value)
        ->set('metode', Sumber::Tunai->value)
        ->call('simpan');

    $r = RincianPd::where('surat_tugas_id', $st->id)->first();
    expect($r)->not->toBeNull()
        ->and($r->nominal)->toBe(1_920_000)
        ->and($r->pegawai_nama)->toBe('Polly')
        ->and($r->pembayar)->toBe(PembayarPd::Pelaksana);
});

it('hapus komponen menghilangkan baris', function () {
    $st = stKelola();
    $r = RincianPd::create([
        'surat_tugas_id' => $st->id, 'pegawai_index' => 0, 'pegawai_nama' => 'Polly',
        'komponen' => KomponenBiaya::Penginapan, 'qty' => 2, 'harga_satuan' => 500_000, 'nominal' => 1_000_000,
        'pembayar' => PembayarPd::Bendahara, 'metode' => Sumber::Bank, 'urutan' => 1,
    ]);

    Livewire::test(KelolaRincian::class, ['suratTugas' => $st])
        ->call('hapus', $r->id);

    expect(RincianPd::find($r->id))->toBeNull();
});

it('menolak qty nol', function () {
    Livewire::test(KelolaRincian::class, ['suratTugas' => stKelola()])
        ->set('qty', 0)
        ->set('harga_satuan', 100_000)
        ->call('simpan')
        ->assertHasErrors(['qty']);
});
