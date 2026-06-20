<?php

use App\Enums\JenisTransaksi;
use App\Enums\Role;
use App\Enums\StatusSpj;
use App\Enums\Sumber;
use App\Models\Tambahan;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Services\SaldoService;
use App\Services\TambahanService;

beforeEach(function () {
    config()->set('kas.saldo_awal_tunai', 0);
    config()->set('kas.saldo_awal_bank', 0);
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
    $this->service = app(TambahanService::class);
    $this->saldo = app(SaldoService::class);
});

function indukKurang(array $attr = []): TransaksiKas
{
    return TransaksiKas::factory()->create(array_merge([
        'jenis' => JenisTransaksi::Belanja,
        'sumber' => Sumber::Tunai,
        'debet' => 0,
        'kredit' => 1_000_000,
        'nilai_spby' => 0,
        'status_spj' => StatusSpj::Belum,
        'kegiatan' => 'Belanja ATK',
    ], $attr));
}

// 1. catat membuat record + baris keluar + tautan
it('catat: record tambahan + baris keluar tertaut dibuat', function () {
    $induk = indukKurang();

    $t = $this->service->catat($induk, ['tanggal' => '2026-06-19', 'jumlah' => 200_000, 'keterangan' => 'nombok belanja']);

    $keluar = TransaksiKas::where('parent_id', $induk->id)
        ->where('jenis', JenisTransaksi::Tambahan)->first();

    expect(Tambahan::find($t->id))->not->toBeNull()
        ->and($keluar)->not->toBeNull()
        ->and($keluar->kredit)->toBe(200_000)
        ->and($keluar->debet)->toBe(0)
        ->and($keluar->sumber)->toBe(Sumber::Tunai)        // ikut induk
        ->and($keluar->parent_id)->toBe($induk->id)
        ->and($t->ref_keluar_id)->toBe($keluar->id);
});

// 2. EFEK SALDO: saldo sumber induk TURUN sebesar jumlah
it('efek saldo: saldo sumber induk turun sebesar jumlah', function () {
    $induk = indukKurang(['sumber' => Sumber::Tunai]);
    $sebelum = $this->saldo->deltaPerSumber(Sumber::Tunai);

    $this->service->catat($induk, ['tanggal' => '2026-06-19', 'jumlah' => 250_000]);

    expect($sebelum - $this->saldo->deltaPerSumber(Sumber::Tunai))->toBe(250_000);
});

// 3. Hapus: baris keluar ikut ter-soft-delete; saldo naik kembali
it('hapus tambahan: baris keluar ikut ter-soft-delete; saldo naik kembali', function () {
    $induk = indukKurang(['sumber' => Sumber::Tunai]);
    $t = $this->service->catat($induk, ['tanggal' => '2026-06-19', 'jumlah' => 300_000]);
    $keluarId = $t->ref_keluar_id;
    $saldoSesudahCatat = $this->saldo->deltaPerSumber(Sumber::Tunai);

    $t->delete();

    expect(Tambahan::find($t->id))->toBeNull()
        ->and(Tambahan::withTrashed()->find($t->id)->trashed())->toBeTrue()
        ->and(TransaksiKas::find($keluarId))->toBeNull()
        ->and(TransaksiKas::withTrashed()->find($keluarId)->trashed())->toBeTrue()
        ->and($this->saldo->deltaPerSumber(Sumber::Tunai) - $saldoSesudahCatat)->toBe(300_000);
});

// 4. Restore: baris keluar pulih; saldo turun lagi
it('restore tambahan: baris keluar bangkit; saldo turun lagi', function () {
    $induk = indukKurang(['sumber' => Sumber::Tunai]);
    $t = $this->service->catat($induk, ['tanggal' => '2026-06-19', 'jumlah' => 300_000]);
    $keluarId = $t->ref_keluar_id;
    $saldoSesudahCatat = $this->saldo->deltaPerSumber(Sumber::Tunai);
    $t->delete();

    $t->restore();

    expect(TransaksiKas::find($keluarId))->not->toBeNull()
        ->and(TransaksiKas::find($keluarId)->trashed())->toBeFalse()
        ->and($this->saldo->deltaPerSumber(Sumber::Tunai))->toBe($saldoSesudahCatat);
});

// 5. Tambahan TIDAK mengubah status_spj (murni dari nota vs target)
it('tambahan tidak mengubah status_spj', function () {
    $induk = indukKurang(['kredit' => 1_000_000, 'status_spj' => StatusSpj::Belum]);

    $this->service->catat($induk, ['tanggal' => '2026-06-19', 'jumlah' => 500_000]);

    expect($induk->fresh()->status_spj)->toBe(StatusSpj::Belum);
});
