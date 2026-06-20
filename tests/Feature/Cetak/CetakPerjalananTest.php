<?php

use App\Enums\Role;
use App\Models\SuratTugas;
use App\Models\User;
use App\Services\SuratTugasService;

beforeEach(function () {
    config()->set('kas.periode_terkunci_hingga', null);
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
});

function buatSuratTugas(): SuratTugas
{
    return app(SuratTugasService::class)->simpan([
        'nomor_surat' => '094/ST/VI/2026',
        'dasar' => 'DIPA 2026',
        'maksud' => 'Koordinasi program kelautan',
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
            ['nama' => 'Budi Santoso', 'nip' => '198601012011011002', 'pangkat' => 'Penata Muda/III-a', 'jabatan' => 'Staf', 'golongan' => 'III/a', 'uang_harian' => 500_000, 'transport' => 1_500_000, 'penginapan' => 500_000, 'biaya' => 4_000_000],
        ],
        'porsi_pelaksana' => 6_000_000,
        'sumber_pelaksana' => 'tunai',
        'porsi_bendahara' => 3_620_000,
        'sumber_bendahara' => 'bank',
        'ttd_ppk' => 'Firdaus Dabamona',
        'nip_ppk' => '19800101',
    ]);
}

// 1. Surat Tugas
it('cetak surat tugas: memuat nomor surat & daftar pegawai', function () {
    $st = buatSuratTugas();

    $this->get(route('cetak.pd.surat-tugas', $st))
        ->assertOk()
        ->assertSee('094/ST/VI/2026')
        ->assertSee('Andi Pratama')
        ->assertSee('Budi Santoso');
});

// 2. Rincian Biaya — kini PER PEGAWAI (?pegawai=indeks); fallback dari JSON
//    pegawai bila belum ada rincian_pd.
it('cetak rincian biaya: dokumen per pegawai dengan total benar', function () {
    $st = buatSuratTugas();

    // Pegawai 0 (Andi): 530.000*4 + 2.000.000 + 1.500.000 = 5.620.000
    $this->get(route('cetak.pd.rincian', ['suratTugas' => $st->id, 'pegawai' => 0]))
        ->assertOk()
        ->assertSee('Andi Pratama')
        ->assertSee('5.620.000');

    // Pegawai 1 (Budi): 500.000*4 + 1.500.000 + 500.000 = 4.000.000
    $this->get(route('cetak.pd.rincian', ['suratTugas' => $st->id, 'pegawai' => 1]))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee('4.000.000');
});

// 2b. Kuitansi PD per pegawai
it('cetak kuitansi PD: memuat nominal & penerima pegawai', function () {
    $st = buatSuratTugas();

    $this->get(route('cetak.pd.kuitansi', ['suratTugas' => $st->id, 'pegawai' => 0]))
        ->assertOk()
        ->assertSee('K U I T A N S I')
        ->assertSee('Andi Pratama')
        ->assertSee('5.620.000');
});

// 5. Kop & pejabat dari config/satker
it('kop & pejabat dari config satker (ubah config -> output berubah)', function () {
    $st = buatSuratTugas();
    config()->set('satker.nama', 'Politeknik KP Sorong UJI');

    $this->get(route('cetak.pd.surat-tugas', $st))
        ->assertSee('Politeknik KP Sorong UJI');
});

// 6. Policy
it('policy: pengguna berhak (ppk) dapat mencetak', function () {
    $st = buatSuratTugas();

    $this->actingAs(User::factory()->role(Role::Ppk)->create())
        ->get(route('cetak.pd.surat-tugas', $st))
        ->assertOk();
});
