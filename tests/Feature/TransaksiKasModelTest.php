<?php

use App\Enums\JenisTransaksi;
use App\Enums\StatusSpj;
use App\Enums\Sumber;
use App\Models\TransaksiKas;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('dapat dibuat dengan atribut lengkap dan di-cast ke enum', function () {
    $trx = TransaksiKas::factory()->create([
        'sumber' => Sumber::Tunai,
        'jenis' => JenisTransaksi::Belanja,
        'status_spj' => StatusSpj::Belum,
        'debet' => 0,
        'kredit' => 250_000,
    ]);

    expect($trx->sumber)->toBe(Sumber::Tunai)
        ->and($trx->jenis)->toBe(JenisTransaksi::Belanja)
        ->and($trx->status_spj)->toBe(StatusSpj::Belum)
        ->and($trx->kredit)->toBe(250_000);
});

it('menghasilkan nomor transaksi otomatis saat belum diisi', function () {
    $trx = TransaksiKas::factory()->create();

    expect($trx->no)->not->toBeNull()
        ->and($trx->no)->toStartWith('KAS');
});

it('nomor transaksi bertambah per tahun', function () {
    $trx1 = TransaksiKas::factory()->create(['tanggal' => '2026-01-10']);
    $trx2 = TransaksiKas::factory()->create(['tanggal' => '2026-01-15']);

    expect($trx1->no)->not->toBe($trx2->no);
});

it('soft delete: transaksi yang dihapus tidak muncul di query default', function () {
    $trx = TransaksiKas::factory()->create();
    $id = $trx->id;

    $trx->delete();

    expect(TransaksiKas::find($id))->toBeNull()
        ->and(TransaksiKas::withTrashed()->find($id))->not->toBeNull();
});

it('restoring transaksi yang di-soft-delete memulihkan data', function () {
    $trx = TransaksiKas::factory()->create();
    $trx->delete();
    $trx->restore();

    expect(TransaksiKas::find($trx->id))->not->toBeNull();
});

it('mencatat aktivitas saat dibuat dan diperbarui', function () {
    $trx = TransaksiKas::factory()->create(['kegiatan' => 'Pembelian ATK']);

    $trx->update(['kegiatan' => 'Pembelian ATK dan Tinta']);

    $logs = Activity::whereSubjectType(TransaksiKas::class)
        ->whereSubjectId($trx->id)
        ->get();

    expect($logs)->toHaveCount(2);
});

it('relasi induk-anak (parent_id) berfungsi untuk transaksi pengembalian', function () {
    $induk = TransaksiKas::factory()->create([
        'jenis' => JenisTransaksi::Belanja,
        'debet' => 0,
        'kredit' => 500_000,
    ]);
    $anak = TransaksiKas::factory()->create([
        'parent_id' => $induk->id,
        'jenis' => JenisTransaksi::Pengembalian,
        'debet' => 50_000,
        'kredit' => 0,
    ]);

    expect($anak->parent->id)->toBe($induk->id)
        ->and($induk->anak)->toHaveCount(1)
        ->and($induk->anak->first()->id)->toBe($anak->id);
});

it('nominal tersimpan sebagai integer rupiah tanpa pembulatan desimal', function () {
    $trx = TransaksiKas::factory()->create(['debet' => 1_234_567]);

    expect($trx->fresh()->debet)->toBe(1_234_567)
        ->and(gettype($trx->fresh()->debet))->toBe('integer');
});

it('menyimpan keterangan provenance untuk jejak asal-usul baris otomatis', function () {
    $trx = TransaksiKas::factory()->create([
        'keterangan' => 'Otomatis dari pengembalian No KAS-2026-0007',
    ]);

    expect($trx->fresh()->keterangan)->toBe('Otomatis dari pengembalian No KAS-2026-0007');
});

it('keterangan boleh kosong (null) untuk transaksi manual biasa', function () {
    $trx = TransaksiKas::factory()->create(['keterangan' => null]);

    expect($trx->fresh()->keterangan)->toBeNull();
});

it('mencatat dibuat_oleh otomatis dari pengguna terautentikasi', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $trx = TransaksiKas::factory()->create();

    expect($trx->fresh()->dibuat_oleh)->toBe($user->id)
        ->and($trx->load('dibuatOleh')->dibuatOleh->is($user))->toBeTrue();
});

it('dibuat_oleh null untuk baris sistem (mis. impor bank) saat tanpa autentikasi', function () {
    $trx = TransaksiKas::factory()->create(['dibuat_oleh' => null]);

    expect($trx->fresh()->dibuat_oleh)->toBeNull();
});

it('dibuat_oleh eksplisit tidak ditimpa oleh pengguna terautentikasi', function () {
    $penginput = User::factory()->create();
    $aktorLain = User::factory()->create();
    $this->actingAs($aktorLain);

    // Baris dibuat atas nama penginput tertentu (mis. service Phase 3)
    $trx = TransaksiKas::factory()->create(['dibuat_oleh' => $penginput->id]);

    expect($trx->fresh()->dibuat_oleh)->toBe($penginput->id);
});
