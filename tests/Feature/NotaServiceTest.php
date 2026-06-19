<?php

use App\Enums\StatusSpj;
use App\Models\MultiNota;
use App\Models\TransaksiKas;
use App\Services\NotaService;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->nota = app(NotaService::class);
});

// 1. nota_total >= kredit, nilai_spby=0 -> LUNAS
it('lunas bila nota_total >= kredit dan nilai_spby 0', function () {
    $t = TransaksiKas::factory()->create([
        'kredit' => 1_000_000,
        'nilai_spby' => 0,
        'status_spj' => StatusSpj::Belum,
    ]);
    MultiNota::factory()->create(['transaksi_id' => $t->id, 'nominal' => 1_000_000]);

    $this->nota->recalc($t);

    expect($t->fresh()->status_spj)->toBe(StatusSpj::Lunas);
});

// 2. nota_total < target -> BELUM
it('belum bila nota_total kurang dari target', function () {
    $t = TransaksiKas::factory()->create([
        'kredit' => 1_000_000,
        'nilai_spby' => 0,
        'status_spj' => StatusSpj::Belum,
    ]);
    MultiNota::factory()->create(['transaksi_id' => $t->id, 'nominal' => 750_000]);

    $this->nota->recalc($t);

    expect($t->fresh()->status_spj)->toBe(StatusSpj::Belum);
});

// 3. nilai_spby>0 jadi target (override kredit)
it('nilai_spby > 0 menjadi target mengalahkan kredit (presedensi)', function () {
    $t = TransaksiKas::factory()->create([
        'kredit' => 5_000_000,       // kalau ini target -> BELUM
        'nilai_spby' => 1_000_000,   // target sebenarnya
        'status_spj' => StatusSpj::Belum,
    ]);
    MultiNota::factory()->create(['transaksi_id' => $t->id, 'nominal' => 1_000_000]);

    $this->nota->recalc($t);

    expect($t->fresh()->status_spj)->toBe(StatusSpj::Lunas);
});

// 4. target=0 -> BELUM (tak pernah lunas tanpa target)
it('belum bila kredit 0 dan nilai_spby 0 walau ada nota (target 0)', function () {
    $t = TransaksiKas::factory()->create([
        'kredit' => 0,
        'nilai_spby' => 0,
        'status_spj' => StatusSpj::Belum,
    ]);
    MultiNota::factory()->create(['transaksi_id' => $t->id, 'nominal' => 500_000]);

    $this->nota->recalc($t);

    expect($t->fresh()->status_spj)->toBe(StatusSpj::Belum);
});

// 5. Boundary: nota_total == target -> LUNAS (>=)
it('boundary nota_total persis sama dengan target -> lunas', function () {
    $t = TransaksiKas::factory()->create([
        'kredit' => 2_500_000,
        'nilai_spby' => 0,
        'status_spj' => StatusSpj::Belum,
    ]);
    MultiNota::factory()->create(['transaksi_id' => $t->id, 'nominal' => 2_500_000]);

    $this->nota->recalc($t);

    expect($t->fresh()->status_spj)->toBe(StatusSpj::Lunas);
});

// 6. Otomatis lewat event nota
it('otomatis: tambah nota -> lunas; ubah nominal -> menyesuaikan; hapus -> belum', function () {
    $t = TransaksiKas::factory()->create([
        'kredit' => 1_000_000,
        'nilai_spby' => 0,
        'status_spj' => StatusSpj::Belum,
    ]);

    $nota = MultiNota::factory()->create(['transaksi_id' => $t->id, 'nominal' => 1_000_000]);
    expect($t->fresh()->status_spj)->toBe(StatusSpj::Lunas);

    $nota->update(['nominal' => 500_000]);
    expect($t->fresh()->status_spj)->toBe(StatusSpj::Belum);

    $nota->update(['nominal' => 1_200_000]);
    expect($t->fresh()->status_spj)->toBe(StatusSpj::Lunas);

    $nota->delete();
    expect($t->fresh()->status_spj)->toBe(StatusSpj::Belum);
});

// 7. kembalianTotal = 0 (lock Phase 2): status murni dari nota
it('kembalian total 0 di Phase 2: tanpa nota status tetap belum walau kredit > 0', function () {
    $t = TransaksiKas::factory()->create([
        'kredit' => 1_000_000,
        'nilai_spby' => 0,
        'status_spj' => StatusSpj::Belum,
    ]);

    $this->nota->recalc($t);

    expect($t->fresh()->status_spj)->toBe(StatusSpj::Belum);
});

// 8. updateQuietly: recalc tidak menambah audit di transaksi
it('recalc memakai updateQuietly: tidak menambah entri audit di transaksi', function () {
    $t = TransaksiKas::factory()->create([
        'kredit' => 1_000_000,
        'nilai_spby' => 0,
        'status_spj' => StatusSpj::Belum,
    ]);

    // forSubject() aman terhadap morph map (alias 'transaksi') -> asersi sungguhan.
    $auditTransaksiAwal = Activity::forSubject($t)->count();

    MultiNota::factory()->create(['transaksi_id' => $t->id, 'nominal' => 1_000_000]);

    expect($t->fresh()->status_spj)->toBe(StatusSpj::Lunas)
        ->and(Activity::forSubject($t)->count())
        ->toBe($auditTransaksiAwal); // status berubah lewat recalc TANPA log baru
});

// 9. Tanpa kolom cache; withSum/withCount benar
it('tidak ada kolom nota_total/nota_jml; withSum & withCount memberi angka benar', function () {
    expect(Schema::hasColumn('transaksi_kas', 'nota_total'))->toBeFalse()
        ->and(Schema::hasColumn('transaksi_kas', 'nota_jml'))->toBeFalse();

    $t = TransaksiKas::factory()->create();
    MultiNota::factory()->create(['transaksi_id' => $t->id, 'nominal' => 300_000]);
    MultiNota::factory()->create(['transaksi_id' => $t->id, 'nominal' => 700_000]);

    $row = TransaksiKas::query()
        ->withCount('nota')
        ->withSum('nota', 'nominal')
        ->find($t->id);

    expect($row->nota_count)->toBe(2)
        ->and((int) $row->nota_sum_nominal)->toBe(1_000_000);
});
