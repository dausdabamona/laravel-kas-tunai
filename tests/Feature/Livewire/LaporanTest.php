<?php

use App\Enums\Role;
use App\Enums\Sumber;
use App\Livewire\Laporan;
use App\Models\TransaksiKas;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('kas.saldo_awal_tunai', 0);
    config()->set('kas.saldo_awal_bank', 0);
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
});

it('menampilkan rekap untuk rentang terpilih', function () {
    TransaksiKas::factory()->create([
        'tanggal' => '2026-06-10', 'sumber' => Sumber::Tunai,
        'debet' => 2_000_000, 'kredit' => 0,
    ]);

    Livewire::test(Laporan::class)
        ->set('dari', '2026-06-01')
        ->set('sampai', '2026-06-30')
        ->assertSet('rekap.tunai.debet', 2_000_000)
        ->assertSee('Cetak BKU');
});
