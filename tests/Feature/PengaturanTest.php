<?php

use App\Enums\Role;
use App\Enums\StatusSpj;
use App\Livewire\Nota\Kelola;
use App\Models\Pengaturan;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Services\PengembalianService;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;

// 1. set/get + cache
it('Pengaturan set/get bertahan; default untuk kunci tak ada; set menimpa satu baris', function () {
    Pengaturan::set('periode_terkunci_hingga', '2026-05-31');

    expect(Pengaturan::get('periode_terkunci_hingga'))->toBe('2026-05-31')
        ->and(Pengaturan::get('tak_ada', 'fallback'))->toBe('fallback');

    Pengaturan::set('periode_terkunci_hingga', '2026-06-30');
    expect(Pengaturan::get('periode_terkunci_hingga'))->toBe('2026-06-30')
        ->and(Pengaturan::where('kunci', 'periode_terkunci_hingga')->count())->toBe(1);
});

// 2. period-lock memblok ≥3 jalur untuk SEMUA peran (termasuk bendahara)
it('periode terkunci memblok transaksi, nota, dan pengembalian — termasuk bendahara', function () {
    Pengaturan::set('periode_terkunci_hingga', '2026-12-31');
    $bendahara = User::factory()->role(Role::Bendahara)->create();
    $this->actingAs($bendahara);

    $trx = TransaksiKas::factory()->create(['tanggal' => '2026-06-10', 'status_spj' => StatusSpj::Belum, 'kredit' => 1_000_000]);

    // jalur 1: policy transaksi
    expect($bendahara->can('update', $trx))->toBeFalse();

    // jalur 2: nota (Livewire authorize update)
    Livewire::test(Kelola::class, ['transaksi' => $trx])
        ->set('nama_penyedia', 'CV X')
        ->set('nominal', 100_000)
        ->call('simpanNota')
        ->assertForbidden();

    // jalur 3: pengembalian (service guard)
    expect(fn () => app(PengembalianService::class)->catat($trx, ['tanggal' => '2026-06-10', 'jumlah' => 50_000]))
        ->toThrow(AuthorizationException::class);
});

it('periode terbuka: bendahara tetap dapat mutasi (kontrol negatif)', function () {
    Pengaturan::set('periode_terkunci_hingga', '2026-05-31');
    $bendahara = User::factory()->role(Role::Bendahara)->create();

    $trx = TransaksiKas::factory()->create(['tanggal' => '2026-06-10', 'status_spj' => StatusSpj::Belum]);

    expect($bendahara->can('update', $trx))->toBeTrue();
});
