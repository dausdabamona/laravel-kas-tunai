<?php

use App\Enums\JenisTransaksi;
use App\Enums\Role;
use App\Enums\Sumber;
use App\Models\SuratTugas;
use App\Models\TransaksiKas;
use App\Models\UangMukaPd;
use App\Models\User;
use App\Services\SaldoService;
use App\Services\SuratTugasService;
use App\Services\UangMukaPdService;

beforeEach(function () {
    config()->set('kas.saldo_awal_tunai', 0);
    config()->set('kas.saldo_awal_bank', 0);
    config()->set('kas.periode_terkunci_hingga', null);
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
    $this->service = app(UangMukaPdService::class);
    $this->saldo = app(SaldoService::class);
});

function stUangMuka(): SuratTugas
{
    // porsi 0 -> tidak ada baris kas dari porsi; kas hanya bergerak dari uang muka.
    return app(SuratTugasService::class)->simpan([
        'nomor_surat' => '050/ST/VI/2026',
        'maksud' => 'Koordinasi',
        'tempat_berangkat' => 'Sorong',
        'tempat_tujuan' => 'Ambon',
        'tgl_berangkat' => '2026-06-12',
        'tgl_kembali' => '2026-06-15',
        'lama_hari' => 4,
        'jenis' => 'luar_kota',
        'kegiatan' => 'PD Ambon',
        'pegawai' => [['nama' => 'Polly', 'nip' => '1', 'biaya' => 0]],
        'porsi_pelaksana' => 0,
        'sumber_pelaksana' => 'tunai',
        'porsi_bendahara' => 0,
        'sumber_bendahara' => null,
    ]);
}

it('catat: record + baris kas keluar (PdUangMuka) tertaut', function () {
    $st = stUangMuka();

    $um = $this->service->catat($st, ['pegawai_index' => 0, 'tanggal' => '2026-06-12', 'jumlah' => 1_920_000, 'metode' => 'tunai']);

    $keluar = TransaksiKas::where('jenis', JenisTransaksi::PdUangMuka)->first();

    expect(UangMukaPd::find($um->id))->not->toBeNull()
        ->and($keluar)->not->toBeNull()
        ->and($keluar->kredit)->toBe(1_920_000)
        ->and($keluar->sumber)->toBe(Sumber::Tunai)
        ->and($keluar->ref_group)->toBe($st->transaksi->ref_group)
        ->and($um->ref_kas_id)->toBe($keluar->id);
});

it('efek saldo: tunai & bank berkurang sesuai metode', function () {
    $st = stUangMuka();

    $this->service->catat($st, ['pegawai_index' => 0, 'tanggal' => '2026-06-12', 'jumlah' => 1_920_000, 'metode' => 'tunai']);
    $this->service->catat($st, ['pegawai_index' => 0, 'tanggal' => '2026-06-12', 'jumlah' => 4_609_636, 'metode' => 'bank']);

    expect($this->saldo->deltaPerSumber(Sumber::Tunai))->toBe(-1_920_000)
        ->and($this->saldo->deltaPerSumber(Sumber::Bank))->toBe(-4_609_636);
});

it('hapus uang muka: baris kas ikut soft-delete; saldo pulih', function () {
    $st = stUangMuka();
    $um = $this->service->catat($st, ['pegawai_index' => 0, 'tanggal' => '2026-06-12', 'jumlah' => 1_000_000, 'metode' => 'bank']);
    $kasId = $um->ref_kas_id;

    $um->delete();

    expect(UangMukaPd::find($um->id))->toBeNull()
        ->and(TransaksiKas::find($kasId))->toBeNull()
        ->and($this->saldo->deltaPerSumber(Sumber::Bank))->toBe(0);
});

it('periode terkunci menolak pencatatan', function () {
    $st = stUangMuka();
    config()->set('kas.periode_terkunci_hingga', '2026-12-31');

    expect(fn () => $this->service->catat($st, ['pegawai_index' => 0, 'tanggal' => '2026-06-12', 'jumlah' => 500_000, 'metode' => 'tunai']))
        ->toThrow(Illuminate\Auth\Access\AuthorizationException::class);
});
