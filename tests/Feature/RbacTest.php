<?php

use App\Enums\Role;
use App\Models\Concerns\Auditable;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Traits\LogsActivity;

it('memiliki empat peran dengan nilai dan label Bahasa Indonesia', function () {
    expect(collect(Role::cases())->map->value->all())
        ->toBe(['operator', 'bendahara', 'ppk', 'pimpinan']);

    expect(Role::Operator->label())->toBe('Operator')
        ->and(Role::Bendahara->label())->toBe('Bendahara')
        ->and(Role::Ppk->label())->toBe('PPK')
        ->and(Role::Pimpinan->label())->toBe('Pimpinan');
});

it('mengonversi kolom role menjadi enum Role', function () {
    $user = User::factory()->create(['role' => Role::Bendahara]);

    expect($user->fresh()->role)->toBe(Role::Bendahara);
});

it('memakai peran operator sebagai bawaan', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe(Role::Operator);
});

it('menyediakan pemeriksa peran pada model User', function () {
    $user = User::factory()->create(['role' => Role::Ppk]);

    expect($user->hasRole(Role::Ppk))->toBeTrue()
        ->and($user->hasRole(Role::Operator))->toBeFalse();
});

it('memberi izin Gate sesuai peran pengguna', function (Role $role, string $ability, bool $allowed) {
    $user = User::factory()->create(['role' => $role]);

    expect(Gate::forUser($user)->allows($ability))->toBe($allowed);
})->with([
    'operator boleh input transaksi' => [Role::Operator, 'input-transaksi', true],
    'operator tidak boleh verifikasi' => [Role::Operator, 'verifikasi-transaksi', false],
    'operator tidak boleh setujui spj' => [Role::Operator, 'setujui-spj', false],
    'operator boleh lihat laporan' => [Role::Operator, 'lihat-laporan', true],

    'bendahara boleh input transaksi' => [Role::Bendahara, 'input-transaksi', true],
    'bendahara boleh verifikasi' => [Role::Bendahara, 'verifikasi-transaksi', true],
    'bendahara tidak boleh setujui spj' => [Role::Bendahara, 'setujui-spj', false],

    'ppk boleh setujui spj' => [Role::Ppk, 'setujui-spj', true],
    'ppk boleh kelola pengguna' => [Role::Ppk, 'kelola-pengguna', true],
    'ppk tidak boleh input transaksi' => [Role::Ppk, 'input-transaksi', false],

    'pimpinan boleh lihat laporan' => [Role::Pimpinan, 'lihat-laporan', true],
    'pimpinan tidak boleh input transaksi' => [Role::Pimpinan, 'input-transaksi', false],
    'pimpinan tidak boleh kelola pengguna' => [Role::Pimpinan, 'kelola-pengguna', false],
]);

it('menghormati Gate untuk pengguna yang sedang login', function () {
    $bendahara = User::factory()->create(['role' => Role::Bendahara]);

    $this->actingAs($bendahara);

    expect(Gate::allows('verifikasi-transaksi'))->toBeTrue()
        ->and(Gate::allows('setujui-spj'))->toBeFalse();
});

it('menyediakan trait Auditable yang menggabungkan SoftDeletes dan LogsActivity', function () {
    $traits = array_keys(class_uses(Auditable::class));

    expect($traits)->toContain(SoftDeletes::class)
        ->and($traits)->toContain(LogsActivity::class);
});
