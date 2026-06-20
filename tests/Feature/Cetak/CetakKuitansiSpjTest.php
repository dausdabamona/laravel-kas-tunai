<?php

use App\Enums\Role;
use App\Enums\StatusSpj;
use App\Models\MultiNota;
use App\Models\TransaksiKas;
use App\Models\User;

beforeEach(function () {
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
});

// 4. Kuitansi
it('Kuitansi: nilai + terbilang benar; penerima dari penyedia', function () {
    $t = TransaksiKas::factory()->create(['kegiatan' => 'jasa cleaning gedung']);
    $nota = MultiNota::factory()->create([
        'transaksi_id' => $t->id,
        'nama_penyedia' => 'CV Mitra Bahari',
        'nominal' => 11_100_000,
    ]);

    $this->get(route('cetak.pajak.kuitansi', $nota))
        ->assertOk()
        ->assertSee('CV Mitra Bahari')
        ->assertSee('11.100.000')
        ->assertSee('sebelas juta seratus ribu rupiah');
});

// 5. SPJ
it('SPJ: daftar nota + status_spj + ringkasan pajak', function () {
    $t = TransaksiKas::factory()->create([
        'kegiatan' => 'jasa cleaning gedung',
        'kredit' => 11_100_000,
        'nilai_spby' => 0,
        'status_spj' => StatusSpj::Belum,
    ]);
    MultiNota::factory()->create([
        'transaksi_id' => $t->id,
        'nama_penyedia' => 'CV Mitra Bahari',
        'nominal' => 11_100_000,
    ]);

    $this->get(route('cetak.pajak.spj', $t))
        ->assertOk()
        ->assertSee('CV Mitra Bahari')           // daftar nota
        ->assertSee('11.100.000')
        ->assertSee('Lunas')                     // status_spj (recalc -> lunas)
        ->assertSee('PPh Pasal 23');             // ringkasan pajak
});

// 6. Kop dari config
it('kop kuitansi dari config satker (ubah config -> output berubah)', function () {
    $t = TransaksiKas::factory()->create(['kegiatan' => 'jasa cleaning']);
    $nota = MultiNota::factory()->create(['transaksi_id' => $t->id, 'nominal' => 1_000_000]);
    config()->set('satker.nama', 'Politeknik KP Sorong UJI KUITANSI');

    $this->get(route('cetak.pajak.kuitansi', $nota))
        ->assertSee('Politeknik KP Sorong UJI KUITANSI');
});

// 7. Policy
it('policy: pimpinan dapat mencetak SPJ', function () {
    $t = TransaksiKas::factory()->create(['kegiatan' => 'jasa cleaning']);
    MultiNota::factory()->create(['transaksi_id' => $t->id, 'nominal' => 1_000_000]);

    $this->actingAs(User::factory()->role(Role::Pimpinan)->create())
        ->get(route('cetak.pajak.spj', $t))
        ->assertOk();
});
