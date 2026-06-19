<?php

use App\Enums\JenisTransaksi;
use App\Enums\Role;
use App\Enums\StatusSpj;
use App\Enums\Sumber;
use App\Models\MultiNota;
use App\Models\Pengembalian;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Services\PengembalianService;
use App\Services\SaldoService;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    config()->set('kas.saldo_awal_tunai', 0);
    config()->set('kas.saldo_awal_bank', 0);
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
    $this->service = app(PengembalianService::class);
    $this->saldo = app(SaldoService::class);
});

function indukBelanja(array $attr = []): TransaksiKas
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

// 1. catat membuat record + baris masuk + tautan
it('catat: record pengembalian + baris masuk tertaut dibuat', function () {
    $induk = indukBelanja();

    $p = $this->service->catat($induk, ['tanggal' => '2026-06-19', 'jumlah' => 200_000, 'keterangan' => 'sisa belanja']);

    $masuk = TransaksiKas::where('parent_id', $induk->id)
        ->where('jenis', JenisTransaksi::Pengembalian)->first();

    expect(Pengembalian::find($p->id))->not->toBeNull()
        ->and($masuk)->not->toBeNull()
        ->and($masuk->debet)->toBe(200_000)
        ->and($masuk->kredit)->toBe(0)
        ->and($masuk->sumber)->toBe(Sumber::Tunai)        // ikut induk
        ->and($masuk->parent_id)->toBe($induk->id)
        ->and($p->ref_masuk_id)->toBe($masuk->id);
});

// 2. EFEK SALDO
it('efek saldo: saldo sumber induk naik sebesar jumlah', function () {
    $induk = indukBelanja(['sumber' => Sumber::Tunai]);
    $sebelum = $this->saldo->deltaPerSumber(Sumber::Tunai);

    $this->service->catat($induk, ['tanggal' => '2026-06-19', 'jumlah' => 250_000]);

    expect($this->saldo->deltaPerSumber(Sumber::Tunai) - $sebelum)->toBe(250_000);
});

// 3. EFEK SPJ: pengembalian menutup sisa setelah nota
it('efek spj: nota di bawah target lalu pengembalian menutup sisa -> lunas', function () {
    $induk = indukBelanja(['kredit' => 1_000_000]);
    MultiNota::factory()->create(['transaksi_id' => $induk->id, 'nominal' => 700_000]);
    expect($induk->fresh()->status_spj)->toBe(StatusSpj::Belum);

    $this->service->catat($induk, ['tanggal' => '2026-06-19', 'jumlah' => 300_000]);

    expect($induk->fresh()->status_spj)->toBe(StatusSpj::Lunas);
});

// 4. Swap teruji: tanpa nota, pengembalian == target -> lunas
it('swap kembalianTotal real: tanpa nota, pengembalian sebesar target -> lunas', function () {
    $induk = indukBelanja(['kredit' => 500_000]);

    $this->service->catat($induk, ['tanggal' => '2026-06-19', 'jumlah' => 500_000]);

    expect($induk->fresh()->status_spj)->toBe(StatusSpj::Lunas);
});

// 5. Hapus: record + baris masuk ikut; saldo & status balik
it('hapus pengembalian: baris masuk ikut ter-soft-delete; saldo turun & status balik', function () {
    $induk = indukBelanja(['kredit' => 1_000_000, 'sumber' => Sumber::Tunai]);
    $p = $this->service->catat($induk, ['tanggal' => '2026-06-19', 'jumlah' => 1_000_000]);
    $masukId = $p->ref_masuk_id;

    expect($induk->fresh()->status_spj)->toBe(StatusSpj::Lunas);
    $saldoSesudahCatat = $this->saldo->deltaPerSumber(Sumber::Tunai);

    $p->delete();

    expect(Pengembalian::find($p->id))->toBeNull()
        ->and(Pengembalian::withTrashed()->find($p->id)->trashed())->toBeTrue()
        ->and(TransaksiKas::find($masukId))->toBeNull()
        ->and(TransaksiKas::withTrashed()->find($masukId)->trashed())->toBeTrue()
        ->and($saldoSesudahCatat - $this->saldo->deltaPerSumber(Sumber::Tunai))->toBe(1_000_000)
        ->and($induk->fresh()->status_spj)->toBe(StatusSpj::Belum);
});

// 6. Restore: record + baris masuk pulih; saldo & status pulih
it('restore pengembalian: baris masuk bangkit; saldo & status pulih', function () {
    $induk = indukBelanja(['kredit' => 1_000_000, 'sumber' => Sumber::Tunai]);
    $p = $this->service->catat($induk, ['tanggal' => '2026-06-19', 'jumlah' => 1_000_000]);
    $masukId = $p->ref_masuk_id;
    $p->delete();

    $p->restore();

    expect(TransaksiKas::find($masukId))->not->toBeNull()
        ->and(TransaksiKas::find($masukId)->trashed())->toBeFalse()
        ->and($this->saldo->deltaPerSumber(Sumber::Tunai))->toBe(0) // -1jt belanja +1jt masuk
        ->and($induk->fresh()->status_spj)->toBe(StatusSpj::Lunas);
});

// 7. Audit: langsung tercatat; cascade baris masuk (bulk) tidak
it('audit: catat & hapus pengembalian tercatat; cascade baris masuk tidak', function () {
    $induk = indukBelanja();
    $p = $this->service->catat($induk, ['tanggal' => '2026-06-19', 'jumlah' => 200_000]);
    $masukId = $p->ref_masuk_id;
    $masuk = TransaksiKas::find($masukId);

    $p->delete();

    expect(Activity::forSubject($p)->count())->toBe(2)        // created, deleted
        ->and(Activity::forSubject($masuk)->count())->toBe(1); // hanya created (cascade bulk tak teraudit)
});
