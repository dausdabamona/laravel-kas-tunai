<?php

use App\Enums\JenisTransaksi;
use App\Enums\Role;
use App\Livewire\PindahDana;
use App\Models\TransaksiKas;
use App\Models\User;
use Livewire\Livewire;

// 6. Livewire submit -> 2 baris + saldo bar
it('livewire: submit form pindah dana membuat 2 baris dan memperbarui saldo bar', function () {
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());

    Livewire::test(PindahDana::class)
        ->set('arah', 'TUNAI_BANK')
        ->set('nominal', 750_000)
        ->set('tanggal', '2026-06-19')
        ->set('keterangan', 'Setor ke bank')
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSet('rekap.bank', 750_000)
        ->assertSet('rekap.tunai', -750_000);

    expect(TransaksiKas::where('jenis', JenisTransaksi::PindahDana)->count())->toBe(2);
});

// 7. Policy: hanya bendahara; periode terkunci memblok
it('policy: operator ditolak pindah dana', function () {
    $this->actingAs(User::factory()->role(Role::Operator)->create());

    Livewire::test(PindahDana::class)
        ->set('arah', 'TUNAI_BANK')
        ->set('nominal', 100_000)
        ->set('tanggal', '2026-06-19')
        ->call('simpan')
        ->assertForbidden();
});

it('policy: periode terkunci memblok bendahara pindah dana', function () {
    config()->set('kas.periode_terkunci_hingga', '2026-12-31');
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());

    Livewire::test(PindahDana::class)
        ->set('arah', 'TUNAI_BANK')
        ->set('nominal', 100_000)
        ->set('tanggal', '2026-06-10')
        ->call('simpan')
        ->assertForbidden();
});
