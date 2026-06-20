<?php

use App\Enums\JenisTransaksi;
use App\Enums\Role;
use App\Enums\StatusRekonsiliasi;
use App\Enums\Sumber;
use App\Models\MultiNota;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Services\PengembalianService;
use App\Services\RekonsiliasiService;
use App\Services\TambahanService;

beforeEach(function () {
    config()->set('kas.saldo_awal_tunai', 0);
    config()->set('kas.saldo_awal_bank', 0);
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
    $this->rekon = app(RekonsiliasiService::class);
});

function indukRekon(array $attr = []): TransaksiKas
{
    return TransaksiKas::factory()->create(array_merge([
        'jenis' => JenisTransaksi::Belanja,
        'sumber' => Sumber::Tunai,
        'debet' => 0,
        'kredit' => 1_000_000,
        'nilai_spby' => 0,
        'uang_diserahkan' => 0,
        'kegiatan' => 'Belanja ATK',
    ], $attr));
}

// 1. Lebih: uang muka > nota -> pelaksana wajib kembalikan
it('selisih lebih: uang muka melebihi nota -> status Lebih', function () {
    $induk = indukRekon(['kredit' => 1_000_000]);
    MultiNota::factory()->create(['transaksi_id' => $induk->id, 'nominal' => 700_000]);

    $r = $this->rekon->untuk($induk->fresh());

    expect($r['selisih'])->toBe(300_000)
        ->and($r['status'])->toBe(StatusRekonsiliasi::Lebih);
});

// 2. Kurang: nota > uang muka -> bendahara wajib menambah
it('selisih kurang: nota melebihi uang muka -> status Kurang', function () {
    $induk = indukRekon(['kredit' => 1_000_000]);
    MultiNota::factory()->create(['transaksi_id' => $induk->id, 'nominal' => 1_200_000]);

    $r = $this->rekon->untuk($induk->fresh());

    expect($r['selisih'])->toBe(-200_000)
        ->and($r['status'])->toBe(StatusRekonsiliasi::Kurang);
});

// 3. Pengembalian menutup kelebihan -> Selesai
it('pengembalian menutup sisa lebih -> status Selesai', function () {
    $induk = indukRekon(['kredit' => 1_000_000]);
    MultiNota::factory()->create(['transaksi_id' => $induk->id, 'nominal' => 700_000]);
    app(PengembalianService::class)->catat($induk, ['tanggal' => '2026-06-19', 'jumlah' => 300_000]);

    $r = $this->rekon->untuk($induk->fresh());

    expect($r['selisih'])->toBe(0)
        ->and($r['status'])->toBe(StatusRekonsiliasi::Selesai);
});

// 4. Tambahan menutup kekurangan -> Selesai
it('tambahan menutup kekurangan -> status Selesai', function () {
    $induk = indukRekon(['kredit' => 1_000_000]);
    MultiNota::factory()->create(['transaksi_id' => $induk->id, 'nominal' => 1_200_000]);
    app(TambahanService::class)->catat($induk, ['tanggal' => '2026-06-19', 'jumlah' => 200_000]);

    $r = $this->rekon->untuk($induk->fresh());

    expect($r['selisih'])->toBe(0)
        ->and($r['status'])->toBe(StatusRekonsiliasi::Selesai);
});

// 5. uang muka = KREDIT (kas keluar), bukan uang_diserahkan yang opsional/stale
it('uang muka memakai kredit (bukan uang_diserahkan yang opsional)', function () {
    $induk = indukRekon(['kredit' => 5_000_000, 'uang_diserahkan' => 1_000_000]);
    MultiNota::factory()->create(['transaksi_id' => $induk->id, 'nominal' => 1_000_000]);

    $r = $this->rekon->untuk($induk->fresh());

    expect($r['uang_muka'])->toBe(5_000_000)           // ikut kredit
        ->and($r['selisih'])->toBe(4_000_000)          // 5jt - 1jt
        ->and($r['status'])->toBe(StatusRekonsiliasi::Lebih);
});
