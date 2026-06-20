<?php

use App\Enums\JenisTransaksi;
use App\Enums\Sumber;
use App\Models\TransaksiKas;
use App\Services\LaporanService;

beforeEach(function () {
    config()->set('kas.saldo_awal_tunai', 1_000_000);
    config()->set('kas.saldo_awal_bank', 0);
    $this->laporan = app(LaporanService::class);
});

function trx(string $tanggal, Sumber $sumber, int $debet, int $kredit, JenisTransaksi $jenis = JenisTransaksi::Belanja): TransaksiKas
{
    return TransaksiKas::factory()->create([
        'tanggal' => $tanggal, 'sumber' => $sumber,
        'debet' => $debet, 'kredit' => $kredit, 'jenis' => $jenis,
    ]);
}

// 1. saldoAwalPeriode
it('saldoAwalPeriode = config + Σ(debet−kredit) tanggal < dari, per sumber', function () {
    trx('2026-05-10', Sumber::Tunai, 500_000, 0);   // sebelum periode
    trx('2026-05-20', Sumber::Tunai, 0, 200_000);   // sebelum periode
    trx('2026-06-05', Sumber::Tunai, 999_999, 0);   // DALAM periode (tak dihitung)
    trx('2026-05-15', Sumber::Bank, 700_000, 0);    // sumber lain

    expect($this->laporan->saldoAwalPeriode(Sumber::Tunai, '2026-06-01'))
        ->toBe(1_300_000) // 1jt config + (500rb - 200rb)
        ->and($this->laporan->saldoAwalPeriode(Sumber::Bank, '2026-06-01'))
        ->toBe(700_000);  // 0 config + 700rb
});

// 2. rekap
it('rekap: saldo_awal + debet − kredit = saldo_akhir per sumber', function () {
    trx('2026-05-10', Sumber::Tunai, 300_000, 0);   // saldo awal: 1jt + 300rb = 1.3jt
    trx('2026-06-05', Sumber::Tunai, 2_000_000, 0);
    trx('2026-06-10', Sumber::Tunai, 0, 500_000);

    $r = $this->laporan->rekap('2026-06-01', '2026-06-30')['tunai'];

    expect($r['saldo_awal'])->toBe(1_300_000)
        ->and($r['debet'])->toBe(2_000_000)
        ->and($r['kredit'])->toBe(500_000)
        ->and($r['saldo_akhir'])->toBe($r['saldo_awal'] + $r['debet'] - $r['kredit'])
        ->and($r['saldo_akhir'])->toBe(2_800_000);
});

it('rekap memuat ringkasan pengembalian dalam rentang', function () {
    trx('2026-06-05', Sumber::Tunai, 250_000, 0, JenisTransaksi::Pengembalian);
    trx('2026-06-06', Sumber::Tunai, 100_000, 0, JenisTransaksi::Belanja);

    expect($this->laporan->rekap('2026-06-01', '2026-06-30')['tunai']['pengembalian'])->toBe(250_000);
});

// 3. bku saldo berjalan
it('bku: baris dalam rentang dengan saldo berjalan mulai dari saldo awal periode', function () {
    trx('2026-06-05', Sumber::Tunai, 500_000, 0);   // saldo: 1jt + 500rb = 1.5jt
    trx('2026-06-10', Sumber::Tunai, 0, 200_000);   // saldo: 1.3jt
    trx('2026-07-05', Sumber::Tunai, 0, 999_999);   // setelah rentang (tak terhitung)

    $bku = $this->laporan->bku(Sumber::Tunai, '2026-06-01', '2026-06-30');

    expect($bku)->toHaveCount(2)
        ->and($bku[0]->saldo_berjalan)->toBe(1_500_000)
        ->and($bku[1]->saldo_berjalan)->toBe(1_300_000);
});

// 4. soft-deleted dikecualikan
it('soft-deleted tidak masuk rekap maupun bku', function () {
    trx('2026-06-05', Sumber::Tunai, 500_000, 0);
    $hapus = trx('2026-06-06', Sumber::Tunai, 9_000_000, 0);
    $hapus->delete();

    $r = $this->laporan->rekap('2026-06-01', '2026-06-30')['tunai'];
    expect($r['debet'])->toBe(500_000)
        ->and($this->laporan->bku(Sumber::Tunai, '2026-06-01', '2026-06-30'))->toHaveCount(1);
});
