<?php

use App\Enums\JenisTransaksi;
use App\Enums\Sumber;
use App\Models\TransaksiKas;
use App\Services\SaldoService;
use App\Services\TransaksiService;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    config()->set('kas.saldo_awal_tunai', 0);
    config()->set('kas.saldo_awal_bank', 0);
    $this->service = app(TransaksiService::class);
    $this->saldo = app(SaldoService::class);
});

// 1. TUNAI_BANK -> dua baris
it('pindah TUNAI_BANK membuat dua baris kredit@tunai & debet@bank, ref_group sama', function () {
    $ref = $this->service->pindahDana('TUNAI_BANK', 500_000, '2026-06-19', null);

    $baris = TransaksiKas::where('ref_group', $ref)->get();
    $keluar = $baris->firstWhere('sumber', Sumber::Tunai);
    $masuk = $baris->firstWhere('sumber', Sumber::Bank);

    expect($baris)->toHaveCount(2)
        ->and($keluar->kredit)->toBe(500_000)
        ->and($keluar->debet)->toBe(0)
        ->and($keluar->jenis)->toBe(JenisTransaksi::PindahDana)
        ->and($masuk->debet)->toBe(500_000)
        ->and($masuk->kredit)->toBe(0)
        ->and($masuk->jenis)->toBe(JenisTransaksi::PindahDana);
});

// 2. Saldo
it('saldo: tunai turun, bank naik, total tetap', function () {
    $this->service->pindahDana('TUNAI_BANK', 500_000, '2026-06-19', null);

    $r = $this->saldo->rekap();
    expect($r['tunai'])->toBe(-500_000)
        ->and($r['bank'])->toBe(500_000)
        ->and($r['total'])->toBe(0);
});

// 3. BANK_TUNAI -> kebalikan
it('pindah BANK_TUNAI: kredit@bank & debet@tunai', function () {
    $ref = $this->service->pindahDana('BANK_TUNAI', 300_000, '2026-06-19', null);

    $baris = TransaksiKas::where('ref_group', $ref)->get();
    expect($baris->firstWhere('sumber', Sumber::Bank)->kredit)->toBe(300_000)
        ->and($baris->firstWhere('sumber', Sumber::Tunai)->debet)->toBe(300_000)
        ->and($this->saldo->rekap()['total'])->toBe(0);
});

// 4. Hapus satu kaki -> pasangan ikut; saldo seimbang
it('hapus satu kaki: pasangan ikut ter-soft-delete; saldo seimbang lagi', function () {
    $ref = $this->service->pindahDana('TUNAI_BANK', 500_000, '2026-06-19', null);
    $satu = TransaksiKas::where('ref_group', $ref)->first();

    $satu->delete();

    expect(TransaksiKas::where('ref_group', $ref)->count())->toBe(0)
        ->and(TransaksiKas::withTrashed()->where('ref_group', $ref)->count())->toBe(2)
        ->and($this->saldo->rekap()['total'])->toBe(0)
        ->and($this->saldo->rekap()['tunai'])->toBe(0)
        ->and($this->saldo->rekap()['bank'])->toBe(0);
});

// 5. Restore -> pasangan pulih
it('restore satu kaki: pasangan pulih; saldo pulih', function () {
    $ref = $this->service->pindahDana('TUNAI_BANK', 500_000, '2026-06-19', null);
    $satu = TransaksiKas::where('ref_group', $ref)->first();
    $satu->delete();

    $satu->restore();

    expect(TransaksiKas::where('ref_group', $ref)->count())->toBe(2)
        ->and($this->saldo->rekap()['tunai'])->toBe(-500_000)
        ->and($this->saldo->rekap()['bank'])->toBe(500_000);
});

// 8. Audit
it('audit: pembuatan baris tercatat; cascade pasangan (bulk) tidak', function () {
    $ref = $this->service->pindahDana('TUNAI_BANK', 500_000, '2026-06-19', null);
    $baris = TransaksiKas::where('ref_group', $ref)->get();
    $a = $baris[0];
    $b = $baris[1];

    $a->delete();

    expect(Activity::forSubject($a)->count())->toBe(2)        // created + deleted
        ->and(Activity::forSubject($b)->count())->toBe(1);    // created saja (cascade bulk)
});
