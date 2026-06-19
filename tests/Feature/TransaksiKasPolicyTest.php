<?php

use App\Enums\Role;
use App\Enums\StatusSpj;
use App\Models\TransaksiKas;
use App\Models\User;

function userDenganPeran(Role $role): User
{
    return User::factory()->role($role)->create();
}

// ── create ───────────────────────────────────────────────────────────────────

it('operator dan bendahara boleh membuat; ppk dan pimpinan tidak', function () {
    expect(userDenganPeran(Role::Operator)->can('create', TransaksiKas::class))->toBeTrue()
        ->and(userDenganPeran(Role::Bendahara)->can('create', TransaksiKas::class))->toBeTrue()
        ->and(userDenganPeran(Role::Ppk)->can('create', TransaksiKas::class))->toBeFalse()
        ->and(userDenganPeran(Role::Pimpinan)->can('create', TransaksiKas::class))->toBeFalse();
});

// ── read ─────────────────────────────────────────────────────────────────────

it('semua peran boleh membaca', function () {
    $trx = TransaksiKas::factory()->create();

    foreach (Role::cases() as $role) {
        expect(userDenganPeran($role)->can('view', $trx))->toBeTrue()
            ->and(userDenganPeran($role)->can('viewAny', TransaksiKas::class))->toBeTrue();
    }
});

// ── delete (pemisahan tugas) ─────────────────────────────────────────────────

it('hanya bendahara yang boleh menghapus; operator tidak (pemisahan tugas)', function () {
    $trx = TransaksiKas::factory()->create();

    expect(userDenganPeran(Role::Bendahara)->can('delete', $trx))->toBeTrue()
        ->and(userDenganPeran(Role::Operator)->can('delete', $trx))->toBeFalse()
        ->and(userDenganPeran(Role::Ppk)->can('delete', $trx))->toBeFalse()
        ->and(userDenganPeran(Role::Pimpinan)->can('delete', $trx))->toBeFalse();
});

// ── update ───────────────────────────────────────────────────────────────────

it('bendahara boleh mengubah transaksi apa pun', function () {
    $lunas = TransaksiKas::factory()->create(['status_spj' => StatusSpj::Lunas]);

    expect(userDenganPeran(Role::Bendahara)->can('update', $lunas))->toBeTrue();
});

it('operator hanya boleh mengubah draft (status belum), bukan yang sudah lunas', function () {
    $draft = TransaksiKas::factory()->create(['status_spj' => StatusSpj::Belum]);
    $lunas = TransaksiKas::factory()->create(['status_spj' => StatusSpj::Lunas]);

    expect(userDenganPeran(Role::Operator)->can('update', $draft))->toBeTrue()
        ->and(userDenganPeran(Role::Operator)->can('update', $lunas))->toBeFalse();
});

it('ppk dan pimpinan tidak boleh mengubah', function () {
    $trx = TransaksiKas::factory()->create(['status_spj' => StatusSpj::Belum]);

    expect(userDenganPeran(Role::Ppk)->can('update', $trx))->toBeFalse()
        ->and(userDenganPeran(Role::Pimpinan)->can('update', $trx))->toBeFalse();
});

// ── period-locking mengalahkan peran ─────────────────────────────────────────

it('period-locking memblok edit dan hapus untuk semua peran termasuk bendahara', function () {
    config()->set('kas.periode_terkunci_hingga', '2026-05-31');

    $terkunci = TransaksiKas::factory()->create([
        'tanggal' => '2026-05-15',
        'status_spj' => StatusSpj::Belum,
    ]);
    $terbuka = TransaksiKas::factory()->create([
        'tanggal' => '2026-06-10',
        'status_spj' => StatusSpj::Belum,
    ]);

    $bendahara = userDenganPeran(Role::Bendahara);

    expect($bendahara->can('update', $terkunci))->toBeFalse()
        ->and($bendahara->can('delete', $terkunci))->toBeFalse()
        // periode terbuka tetap normal
        ->and($bendahara->can('update', $terbuka))->toBeTrue()
        ->and($bendahara->can('delete', $terbuka))->toBeTrue();
});
