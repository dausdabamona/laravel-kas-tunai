<?php

use App\Enums\Role;
use App\Enums\Sumber;
use App\Models\TransaksiKas;
use App\Models\User;

beforeEach(function () {
    config()->set('kas.saldo_awal_tunai', 1_000_000);
    config()->set('kas.saldo_awal_bank', 0);
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
});

function isiLaporan(): void
{
    TransaksiKas::factory()->create([
        'tanggal' => '2026-06-05', 'sumber' => Sumber::Tunai,
        'debet' => 500_000, 'kredit' => 0, 'kegiatan' => 'Terima UP',
    ]);
}

// 5a. BKU render + kop
it('BKU render dengan kop config/satker dan baris kronologis', function () {
    isiLaporan();
    config()->set('satker.nama', 'Politeknik KP Sorong UJI BKU');

    $this->get(route('laporan.bku', ['sumber' => 'tunai', 'dari' => '2026-06-01', 'sampai' => '2026-06-30']))
        ->assertOk()
        ->assertSee('Politeknik KP Sorong UJI BKU')
        ->assertSee('Terima UP')
        ->assertSee('1.500.000'); // saldo berjalan
});

// 5b. LPJ render + kop
it('LPJ render dengan kop dan saldo awal/akhir per sumber', function () {
    isiLaporan();
    config()->set('satker.nama', 'Politeknik KP Sorong UJI LPJ');

    $this->get(route('laporan.lpj', ['dari' => '2026-06-01', 'sampai' => '2026-06-30']))
        ->assertOk()
        ->assertSee('Politeknik KP Sorong UJI LPJ')
        ->assertSee('Saldo Awal')
        ->assertSee('Saldo Akhir');
});

// 6. policy
it('policy: bendahara & pimpinan dapat membaca laporan', function () {
    isiLaporan();

    foreach ([Role::Bendahara, Role::Pimpinan] as $role) {
        $this->actingAs(User::factory()->role($role)->create())
            ->get(route('laporan.lpj', ['dari' => '2026-06-01', 'sampai' => '2026-06-30']))
            ->assertOk();
    }
});
