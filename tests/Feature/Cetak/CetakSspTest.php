<?php

use App\Enums\Role;
use App\Models\MultiNota;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Services\PajakService;

beforeEach(function () {
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
});

/** Transaksi + nota penyedia untuk dasar SSP. */
function notaUntukSsp(string $kegiatan, int $nominal, ?string $npwp = '01.234.567.8-901.000'): MultiNota
{
    $t = TransaksiKas::factory()->create(['kegiatan' => $kegiatan]);

    return MultiNota::factory()->create([
        'transaksi_id' => $t->id,
        'nama_penyedia' => 'CV Mitra Bahari',
        'npwp_penyedia' => $npwp,
        'alamat_penyedia' => 'Jl. Pelabuhan No. 9',
        'nominal' => $nominal,
    ]);
}

// 1. SSP PPh
it('SSP PPh: dpp/pph/map/kjs sesuai hitung(); NPWP & nama penyedia muncul', function () {
    $nota = notaUntukSsp('jasa cleaning gedung kantor', 11_100_000);
    $k = app(PajakService::class)->klasifikasi('jasa cleaning gedung kantor');
    $h = app(PajakService::class)->hitung(11_100_000, $k, true);

    $this->get(route('cetak.pajak.ssp-pph', $nota))
        ->assertOk()
        ->assertSee('CV Mitra Bahari')
        ->assertSee('01.234.567.8-901.000')
        ->assertSee($k['map_pph'])               // 411124
        ->assertSee($k['kjs_pph'])               // 104
        ->assertSee(number_format($h['dpp'], 0, ',', '.'))
        ->assertSee(number_format($h['pph'], 0, ',', '.'));
});

// 2. SSP PPh manual (honor PPh 21)
it('SSP PPh manual (honor PPh 21): field kosong + catatan manual, bukan angka 0 final', function () {
    $nota = notaUntukSsp('honor narasumber kegiatan', 2_000_000);

    $this->get(route('cetak.pajak.ssp-pph', $nota))
        ->assertOk()
        ->assertSee('tabel PPh 21')               // catatan manual
        ->assertSee('411121');                    // MAP PPh 21 tetap muncul
});

// 3. SSP PPN
it('SSP PPN (kategori ber-PPN >= ambang): dpp/ppn + map 411211 + kjs config', function () {
    $nota = notaUntukSsp('jasa cleaning gedung kantor', 11_100_000);
    $k = app(PajakService::class)->klasifikasi('jasa cleaning gedung kantor');
    $h = app(PajakService::class)->hitung(11_100_000, $k, true);

    $this->get(route('cetak.pajak.ssp-ppn', $nota))
        ->assertOk()
        ->assertSee(number_format($h['ppn'], 0, ',', '.'))
        ->assertSee(config('pajak.ppn_ssp.map'))  // 411211
        ->assertSee(config('pajak.ppn_ssp.kjs')); // 920
});

// 6. Kop dari config
it('kop SSP dari config satker (ubah config -> output berubah)', function () {
    $nota = notaUntukSsp('jasa cleaning gedung', 11_100_000);
    config()->set('satker.nama', 'Politeknik KP Sorong UJI SSP');

    $this->get(route('cetak.pajak.ssp-pph', $nota))
        ->assertSee('Politeknik KP Sorong UJI SSP');
});

// 7. Policy
it('policy: ppk dapat mencetak SSP', function () {
    $nota = notaUntukSsp('jasa cleaning gedung', 11_100_000);

    $this->actingAs(User::factory()->role(Role::Ppk)->create())
        ->get(route('cetak.pajak.ssp-pph', $nota))
        ->assertOk();
});
