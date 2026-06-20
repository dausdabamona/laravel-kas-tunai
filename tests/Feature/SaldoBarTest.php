<?php

use App\Enums\Role;
use App\Models\User;

it('bendahara melihat strip saldo kas (total/tunai/bank) di semua halaman', function () {
    $this->actingAs(User::factory()->role(Role::Bendahara)->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Saldo Kas')
        ->assertSee('Tunai:')
        ->assertSee('Bank:');
});

it('peran selain bendahara tidak melihat strip saldo kas', function () {
    foreach ([Role::Operator, Role::Ppk, Role::Pimpinan] as $role) {
        $this->actingAs(User::factory()->role($role)->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Saldo Kas');
    }
});
