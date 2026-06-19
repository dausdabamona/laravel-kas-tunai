<?php

use App\Enums\JenisTransaksi;
use App\Enums\Role;
use App\Enums\Sumber;
use App\Models\SuratTugas;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Services\SaldoService;
use App\Services\SuratTugasService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    config()->set('kas.saldo_awal_tunai', 0);
    config()->set('kas.saldo_awal_bank', 0);
    config()->set('kas.periode_terkunci_hingga', null);
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
    $this->service = app(SuratTugasService::class);
    $this->saldo = app(SaldoService::class);
});

function dataSuratTugas(array $override = []): array
{
    return array_merge([
        'nomor_surat' => '094/ST/2026',
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
            ['nama' => 'Andi', 'nip' => '1', 'pangkat' => 'III/a', 'jabatan' => 'Staf', 'golongan' => 'III', 'uang_harian' => 530_000, 'transport' => 2_000_000, 'penginapan' => 1_500_000, 'biaya' => 5_620_000],
            ['nama' => 'Budi', 'nip' => '2', 'pangkat' => 'III/b', 'jabatan' => 'Staf', 'golongan' => 'III', 'uang_harian' => 500_000, 'transport' => 1_500_000, 'penginapan' => 500_000, 'biaya' => 4_000_000],
        ],
        'porsi_pelaksana' => 6_000_000,
        'sumber_pelaksana' => 'tunai',
        'porsi_bendahara' => 3_620_000,
        'sumber_bendahara' => 'bank',
        'ttd_ppk' => 'Firdaus Dabamona',
        'nip_ppk' => '19800101',
    ], $override);
}

// 1. record + baris pokok
it('simpan: record surat_tugas + baris POKOK (PD_POKOK) tertaut', function () {
    $st = $this->service->simpan(dataSuratTugas());

    $pokok = TransaksiKas::find($st->transaksi_id);
    expect(SuratTugas::find($st->id))->not->toBeNull()
        ->and($pokok)->not->toBeNull()
        ->and($pokok->jenis)->toBe(JenisTransaksi::PdPokok)
        ->and($pokok->kredit)->toBe(6_000_000)
        ->and($pokok->sumber)->toBe(Sumber::Tunai);
});

// 2. baris bendahara
it('porsi_bendahara > 0: baris BENDAHARA (PD_BENDAHARA, sumber_bendahara, ref_group sama)', function () {
    $st = $this->service->simpan(dataSuratTugas());
    $pokok = TransaksiKas::find($st->transaksi_id);

    $bendahara = TransaksiKas::where('jenis', JenisTransaksi::PdBendahara)->first();
    expect($bendahara)->not->toBeNull()
        ->and($bendahara->kredit)->toBe(3_620_000)
        ->and($bendahara->sumber)->toBe(Sumber::Bank)
        ->and($bendahara->ref_group)->toBe($pokok->ref_group)
        ->and($pokok->ref_group)->toStartWith('PD-');
});

it('porsi_bendahara = 0: tidak membuat baris bendahara', function () {
    $this->service->simpan(dataSuratTugas(['porsi_bendahara' => 0, 'sumber_bendahara' => null]));

    expect(TransaksiKas::where('jenis', JenisTransaksi::PdBendahara)->count())->toBe(0);
});

// 3. biaya_total
it('biaya_total = jumlah biaya seluruh pegawai', function () {
    $st = $this->service->simpan(dataSuratTugas());

    expect($st->biaya_total)->toBe(9_620_000); // 5.620.000 + 4.000.000
});

// 4. saldo terpisah per sumber
it('saldo: kredit di sumber_pelaksana & sumber_bendahara terpisah benar', function () {
    $this->service->simpan(dataSuratTugas());

    expect($this->saldo->deltaPerSumber(Sumber::Tunai))->toBe(-6_000_000)
        ->and($this->saldo->deltaPerSumber(Sumber::Bank))->toBe(-3_620_000);
});

// 5. cascade
it('cascade: hapus baris pokok -> surat_tugas + pasangan bendahara ikut; restore -> pulih', function () {
    $st = $this->service->simpan(dataSuratTugas());
    $pokok = TransaksiKas::find($st->transaksi_id);
    $bendaharaId = TransaksiKas::where('jenis', JenisTransaksi::PdBendahara)->first()->id;

    $pokok->delete();

    expect(SuratTugas::find($st->id))->toBeNull()
        ->and(SuratTugas::withTrashed()->find($st->id)->trashed())->toBeTrue()
        ->and(TransaksiKas::find($bendaharaId))->toBeNull()
        ->and(TransaksiKas::withTrashed()->find($bendaharaId)->trashed())->toBeTrue();

    $pokok->restore();

    expect(SuratTugas::find($st->id))->not->toBeNull()
        ->and(TransaksiKas::find($bendaharaId))->not->toBeNull();
});

// 6. pegawai JSON round-trip
it('pegawai JSON round-trip utuh', function () {
    $st = $this->service->simpan(dataSuratTugas())->fresh();

    expect($st->pegawai)->toBeArray()
        ->and($st->pegawai)->toHaveCount(2)
        ->and($st->pegawai[0]['nama'])->toBe('Andi')
        ->and($st->pegawai[1]['biaya'])->toBe(4_000_000);
});

// 7. morph map
it('penjaga: morph map memuat suratTugas', function () {
    expect(array_values(Relation::morphMap()))->toContain(SuratTugas::class);
});

// 8. policy
it('ability perjalanan-dinas: ppk & bendahara boleh; operator & pimpinan tidak', function () {
    $ppk = User::factory()->role(Role::Ppk)->create();
    $bendahara = User::factory()->role(Role::Bendahara)->create();
    $operator = User::factory()->role(Role::Operator)->create();
    $pimpinan = User::factory()->role(Role::Pimpinan)->create();

    expect(Gate::forUser($ppk)->allows('perjalanan-dinas'))->toBeTrue()
        ->and(Gate::forUser($bendahara)->allows('perjalanan-dinas'))->toBeTrue()
        ->and(Gate::forUser($operator)->allows('perjalanan-dinas'))->toBeFalse()
        ->and(Gate::forUser($pimpinan)->allows('perjalanan-dinas'))->toBeFalse();
});

it('periode terkunci: simpan ditolak untuk tanggal di periode terkunci', function () {
    config()->set('kas.periode_terkunci_hingga', '2026-12-31');

    expect(fn () => $this->service->simpan(dataSuratTugas(['tgl_berangkat' => '2026-06-20'])))
        ->toThrow(AuthorizationException::class);
});

// 9. audit
it('audit: pembuatan tercatat; cascade (bulk) tidak', function () {
    $st = $this->service->simpan(dataSuratTugas());
    $pokok = TransaksiKas::find($st->transaksi_id);
    $bendahara = TransaksiKas::where('jenis', JenisTransaksi::PdBendahara)->first();

    $pokok->delete();

    expect(Activity::forSubject($pokok)->count())->toBe(2)         // created + deleted
        ->and(Activity::forSubject($bendahara)->count())->toBe(1)  // created (cascade bulk)
        ->and(Activity::forSubject($st)->count())->toBe(1);        // created (cascade bulk)
});
