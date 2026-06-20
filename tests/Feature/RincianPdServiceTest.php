<?php

use App\Enums\KomponenBiaya;
use App\Enums\PembayarPd;
use App\Enums\Role;
use App\Enums\Sumber;
use App\Models\RincianPd;
use App\Models\SuratTugas;
use App\Models\User;
use App\Services\RincianPdService;
use App\Services\SuratTugasService;

beforeEach(function () {
    config()->set('kas.saldo_awal_tunai', 0);
    config()->set('kas.saldo_awal_bank', 0);
    config()->set('kas.periode_terkunci_hingga', null);
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
    $this->rincian = app(RincianPdService::class);
});

function stUntukRincian(int $porsiPelaksana = 0, int $porsiBendahara = 0): SuratTugas
{
    return app(SuratTugasService::class)->simpan([
        'nomor_surat' => '026/DPKAKP.KKP/VI/2026',
        'maksud' => 'Penguji ANKAPIN/ATKAPIN',
        'tempat_berangkat' => 'Sorong',
        'tempat_tujuan' => 'Ambon',
        'tgl_berangkat' => '2026-06-12',
        'tgl_kembali' => '2026-06-15',
        'lama_hari' => 4,
        'jenis' => 'luar_kota',
        'kegiatan' => 'Perjalanan Dinas Ambon',
        'pegawai' => [
            ['nama' => 'Polly Stenly Barly Christian', 'nip' => '197706082003121004', 'biaya' => 6_529_636],
        ],
        'porsi_pelaksana' => $porsiPelaksana,
        'sumber_pelaksana' => 'tunai',
        'porsi_bendahara' => $porsiBendahara,
        'sumber_bendahara' => 'bank',
    ]);
}

/** Rincian persis dari screenshot (a.n. Polly). */
function rincianPolly(SuratTugas $st): void
{
    $items = [
        ['komponen' => KomponenBiaya::UangHarian, 'uraian' => '4 hari × Rp 480.000', 'qty' => 4, 'harga_satuan' => 480_000, 'nominal' => 1_920_000, 'pembayar' => PembayarPd::Pelaksana, 'metode' => Sumber::Tunai, 'keterangan' => 'Lumpsum'],
        ['komponen' => KomponenBiaya::TransportUdara, 'uraian' => 'Ambon - Sorong', 'qty' => 1, 'harga_satuan' => 1_149_136, 'nominal' => 1_149_136, 'pembayar' => PembayarPd::Bendahara, 'metode' => Sumber::Bank],
        ['komponen' => KomponenBiaya::TransportUdara, 'uraian' => 'Sorong - Ambon', 'qty' => 1, 'harga_satuan' => 1_108_500, 'nominal' => 1_108_500, 'pembayar' => PembayarPd::Bendahara, 'metode' => Sumber::Bank],
        ['komponen' => KomponenBiaya::Penginapan, 'uraian' => '4 malam', 'qty' => 4, 'harga_satuan' => 588_000, 'nominal' => 2_352_000, 'pembayar' => PembayarPd::Bendahara, 'metode' => Sumber::Bank],
    ];

    foreach ($items as $i => $data) {
        RincianPd::create(array_merge($data, [
            'surat_tugas_id' => $st->id,
            'pegawai_index' => 0,
            'pegawai_nama' => 'Polly Stenly Barly Christian',
            'urutan' => $i + 1,
        ]));
    }
}

it('ringkas: total + per metode + per pembayar + bucket sesuai screenshot', function () {
    $st = stUntukRincian();
    rincianPolly($st);

    $r = $this->rincian->ringkas($st->fresh());

    expect($r['total'])->toBe(6_529_636)
        ->and($r['per_metode']['tunai'])->toBe(1_920_000)
        ->and($r['per_metode']['bank'])->toBe(4_609_636)
        ->and($r['per_pembayar']['pelaksana'])->toBe(1_920_000)
        ->and($r['per_pembayar']['bendahara'])->toBe(4_609_636)
        ->and($r['bucket']['pelaksana|tunai'])->toBe(1_920_000)
        ->and($r['bucket']['bendahara|bank'])->toBe(4_609_636);
});

it('telah_dibayar 0 saat porsi belum diposting -> sisa kurang = total', function () {
    $st = stUntukRincian(0, 0);
    rincianPolly($st);

    $r = $this->rincian->ringkas($st->fresh());

    expect($r['telah_dibayar'])->toBe(0)
        ->and($r['sisa_kurang'])->toBe(6_529_636);
});

it('telah_dibayar = Σ uang muka -> sisa kurang 0 bila lunas', function () {
    $st = stUntukRincian();
    rincianPolly($st);

    $um = app(App\Services\UangMukaPdService::class);
    $um->catat($st, ['pegawai_index' => 0, 'tanggal' => '2026-06-12', 'jumlah' => 1_920_000, 'metode' => 'tunai']);
    $um->catat($st, ['pegawai_index' => 0, 'tanggal' => '2026-06-12', 'jumlah' => 4_609_636, 'metode' => 'bank']);

    $r = $this->rincian->ringkas($st->fresh());

    expect($r['telah_dibayar'])->toBe(6_529_636)
        ->and($r['sisa_kurang'])->toBe(0);
});

it('ringkasPegawai: hanya item pegawai tsb + total + telah dibayar uang muka', function () {
    $st = stUntukRincian();
    rincianPolly($st);
    app(App\Services\UangMukaPdService::class)
        ->catat($st, ['pegawai_index' => 0, 'tanggal' => '2026-06-12', 'jumlah' => 1_920_000, 'metode' => 'tunai']);

    $p = $this->rincian->ringkasPegawai($st->fresh(), 0);

    expect($p['nama'])->toBe('Polly Stenly Barly Christian')
        ->and($p['items'])->toHaveCount(4)
        ->and($p['ringkas']['total'])->toBe(6_529_636)
        ->and($p['ringkas']['bucket']['bendahara|bank'])->toBe(4_609_636)
        ->and($p['ringkas']['telah_dibayar'])->toBe(1_920_000)
        ->and($p['ringkas']['sisa_kurang'])->toBe(4_609_636);
});
