<?php

use App\Enums\Role;
use App\Livewire\Pengaturan as PengaturanComponent;
use App\Models\Pengaturan;
use App\Models\User;
use Livewire\Livewire;

// 3. Livewire set kunci periode: bendahara/ppk boleh; operator/pimpinan ditolak
it('bendahara dapat mengunci periode', function () {
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());

    Livewire::test(PengaturanComponent::class)
        ->set('periodeTerkunciHingga', '2026-05-31')
        ->call('simpan')
        ->assertHasNoErrors();

    expect(Pengaturan::get('periode_terkunci_hingga'))->toBe('2026-05-31');
});

it('ppk dapat mengunci periode', function () {
    $this->actingAs(User::factory()->role(Role::Ppk)->create());

    Livewire::test(PengaturanComponent::class)
        ->set('periodeTerkunciHingga', '2026-05-31')
        ->call('simpan')
        ->assertHasNoErrors();
});

it('operator ditolak mengunci periode', function () {
    $this->actingAs(User::factory()->role(Role::Operator)->create());

    Livewire::test(PengaturanComponent::class)
        ->set('periodeTerkunciHingga', '2026-05-31')
        ->call('simpan')
        ->assertForbidden();
});

it('pimpinan ditolak mengunci periode', function () {
    $this->actingAs(User::factory()->role(Role::Pimpinan)->create());

    Livewire::test(PengaturanComponent::class)
        ->set('periodeTerkunciHingga', '2026-05-31')
        ->call('simpan')
        ->assertForbidden();
});
