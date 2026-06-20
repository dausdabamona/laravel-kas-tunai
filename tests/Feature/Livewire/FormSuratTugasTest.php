<?php

use App\Enums\Role;
use App\Livewire\Perjalanan\FormSuratTugas;
use App\Models\SuratTugas;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('kas.periode_terkunci_hingga', null);
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
});

it('lama hari dihitung otomatis (inklusif) dari berangkat-kembali', function () {
    Livewire::test(FormSuratTugas::class)
        ->set('tgl_berangkat', '2026-06-12')
        ->set('tgl_kembali', '2026-06-15')
        ->assertSet('lamaHari', 4); // 12,13,14,15 = 4 hari
});

it('buat surat tugas: tersimpan dengan lama_hari & tanggal_surat benar', function () {
    Livewire::test(FormSuratTugas::class)
        ->set('nomor_surat', '100/ST/VI/2026')
        ->set('tanggal_surat', '2026-06-10')
        ->set('tempat_berangkat', 'Sorong')
        ->set('tempat_tujuan', 'Makassar')
        ->set('maksud', 'Koordinasi')
        ->set('kegiatan', 'PD Makassar')
        ->set('tgl_berangkat', '2026-06-12')
        ->set('tgl_kembali', '2026-06-16')
        ->set('pegawai', [['nama' => 'Andi', 'nip' => '1', 'pangkat' => '', 'jabatan' => '', 'golongan' => '']])
        ->call('simpan')
        ->assertRedirect(route('perjalanan-dinas.index'));

    $st = SuratTugas::first();
    expect($st)->not->toBeNull()
        ->and($st->nomor_surat)->toBe('100/ST/VI/2026')
        ->and($st->tanggal_surat->format('Y-m-d'))->toBe('2026-06-10')
        ->and($st->tempat_tujuan)->toBe('Makassar')
        ->and($st->lama_hari)->toBe(5) // 12..16 inklusif
        ->and($st->transaksi->kegiatan)->toBe('PD Makassar');
});

it('menolak tanggal kembali sebelum berangkat', function () {
    Livewire::test(FormSuratTugas::class)
        ->set('nomor_surat', '101/ST')
        ->set('tempat_berangkat', 'Sorong')
        ->set('tempat_tujuan', 'Ambon')
        ->set('maksud', 'X')
        ->set('kegiatan', 'Y')
        ->set('tgl_berangkat', '2026-06-15')
        ->set('tgl_kembali', '2026-06-12')
        ->set('pegawai', [['nama' => 'Andi', 'nip' => '', 'pangkat' => '', 'jabatan' => '', 'golongan' => '']])
        ->call('simpan')
        ->assertHasErrors(['tgl_kembali']);
});

it('ubah surat tugas: lama_hari dihitung ulang & header tersimpan', function () {
    $st = app(App\Services\SuratTugasService::class)->simpan([
        'nomor_surat' => '090/ST',
        'maksud' => 'Awal',
        'tempat_berangkat' => 'Sorong',
        'tempat_tujuan' => 'Ambon',
        'tgl_berangkat' => '2026-06-12',
        'tgl_kembali' => '2026-06-13',
        'lama_hari' => 2,
        'jenis' => 'luar_kota',
        'kegiatan' => 'Awal',
        'pegawai' => [['nama' => 'Andi', 'biaya' => 0]],
        'porsi_pelaksana' => 0,
        'sumber_pelaksana' => 'tunai',
        'porsi_bendahara' => 0,
        'sumber_bendahara' => null,
    ]);

    Livewire::test(FormSuratTugas::class, ['suratTugasId' => $st->id])
        ->assertSet('nomor_surat', '090/ST')
        ->set('tempat_tujuan', 'Manokwari')
        ->set('tgl_kembali', '2026-06-16')
        ->call('simpan')
        ->assertRedirect(route('perjalanan-dinas.index'));

    $st->refresh();
    expect($st->tempat_tujuan)->toBe('Manokwari')
        ->and($st->lama_hari)->toBe(5); // 12..16
});
