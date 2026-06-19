<?php

use App\Enums\Role;
use App\Models\SuratTugas;
use App\Models\User;
use App\Services\SuratTugasService;

beforeEach(function () {
    config()->set('kas.periode_terkunci_hingga', null);
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
});

function buatST(): SuratTugas
{
    return app(SuratTugasService::class)->simpan([
        'nomor_surat' => '094/ST/VI/2026',
        'dasar' => 'DIPA 2026',
        'maksud' => 'Koordinasi program',
        'angkutan' => 'Pesawat',
        'tempat_berangkat' => 'Sorong',
        'tempat_tujuan' => 'Jakarta',
        'tgl_berangkat' => '2026-06-20',
        'tgl_kembali' => '2026-06-23',
        'lama_hari' => 4,
        'akun' => '524111',
        'jenis' => 'luar_kota',
        'kegiatan' => 'Perjalanan Dinas Jakarta',
        'pegawai' => [
            ['nama' => 'Andi Pratama', 'nip' => '198501012010011001', 'pangkat' => 'Penata/III-c', 'jabatan' => 'Staf', 'golongan' => 'III/c', 'uang_harian' => 530_000, 'transport' => 2_000_000, 'penginapan' => 1_500_000, 'biaya' => 5_620_000],
        ],
        'porsi_pelaksana' => 5_620_000,
        'sumber_pelaksana' => 'tunai',
        'porsi_bendahara' => 0,
        'sumber_bendahara' => null,
        'ttd_ppk' => 'Firdaus Dabamona',
        'nip_ppk' => '19800101',
    ]);
}

// 3. SPD
it('cetak SPD: data berangkat/tujuan/tanggal muncul', function () {
    $st = buatST();

    $this->get(route('cetak.pd.spd', $st))
        ->assertOk()
        ->assertSee('Sorong')
        ->assertSee('Jakarta')
        ->assertSee('Andi Pratama')
        ->assertSee('094/ST/VI/2026');
});

// 4. Pengesahan & Pengeluaran Riil
it('cetak pengesahan ter-render dengan tempat tujuan', function () {
    $st = buatST();

    $this->get(route('cetak.pd.pengesahan', $st))
        ->assertOk()
        ->assertSee('Jakarta');
});

it('cetak daftar pengeluaran riil ter-render dengan total biaya', function () {
    $st = buatST();

    $this->get(route('cetak.pd.pengeluaran-riil', $st))
        ->assertOk()
        ->assertSee('Andi Pratama')
        ->assertSee('5.620.000');
});
