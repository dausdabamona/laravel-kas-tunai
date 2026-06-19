<?php

use App\Models\MasterPenyedia;
use App\Models\MultiNota;
use App\Models\TransaksiKas;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

// 1. Persist + relasi induk
it('nota persist dengan transaksi_id dan muncul di relasi nota induk', function () {
    $transaksi = TransaksiKas::factory()->create();
    MultiNota::factory()->create([
        'transaksi_id' => $transaksi->id,
        'nama_penyedia' => 'CV Uji Coba',
    ]);

    expect($transaksi->nota()->count())->toBe(1)
        ->and($transaksi->nota()->first()->nama_penyedia)->toBe('CV Uji Coba');
});

// 2. Casts
it('nominal cast integer dan tgl_nota cast date', function () {
    $nota = MultiNota::factory()->create([
        'nominal' => 1_500_000,
        'tgl_nota' => '2026-06-19',
    ])->fresh();

    expect($nota->nominal)->toBe(1_500_000)
        ->and(gettype($nota->nominal))->toBe('integer')
        ->and($nota->tgl_nota)->toBeInstanceOf(Carbon::class)
        ->and($nota->tgl_nota->format('Y-m-d'))->toBe('2026-06-19');
});

// 3. Soft-delete nota
it('soft-delete nota: hilang dari relasi normal, tetap ada via withTrashed', function () {
    $transaksi = TransaksiKas::factory()->create();
    $nota = MultiNota::factory()->create(['transaksi_id' => $transaksi->id]);

    $nota->delete();

    expect($transaksi->nota()->count())->toBe(0)
        ->and(MultiNota::find($nota->id))->toBeNull()
        ->and(MultiNota::withTrashed()->find($nota->id))->not->toBeNull();
});

// 4. Cascade soft-delete
it('cascade: soft-delete induk menghapus-lunak seluruh nota anak', function () {
    $transaksi = TransaksiKas::factory()->create();
    MultiNota::factory()->count(2)->create(['transaksi_id' => $transaksi->id]);

    $transaksi->delete();

    expect(MultiNota::where('transaksi_id', $transaksi->id)->count())->toBe(0)
        ->and(MultiNota::withTrashed()->where('transaksi_id', $transaksi->id)->count())->toBe(2);
});

// 5. Restore selektif (timestamp identik)
it('restore selektif: hanya nota cascade yang bangkit, nota terhapus manual tetap terhapus', function () {
    $transaksi = TransaksiKas::factory()->create();
    $notaX = MultiNota::factory()->create(['transaksi_id' => $transaksi->id]);
    $notaY = MultiNota::factory()->create(['transaksi_id' => $transaksi->id]);

    // X dihapus MANUAL lebih dulu. TANPA jeda buatan: presisi mikrodetik
    // memisahkan deleted_at X dari induk secara alami (logika prod yang menjaga).
    $notaX->delete();

    // Induk dihapus (cascade hapus Y dengan timestamp induk PERSIS)
    $transaksi->delete();

    // Induk di-restore -> hanya Y (deleted_at == induk) yang bangkit
    $transaksi->restore();

    expect(MultiNota::withTrashed()->find($notaX->id)->trashed())->toBeTrue()
        ->and(MultiNota::find($notaY->id))->not->toBeNull()
        ->and(MultiNota::find($notaY->id)->trashed())->toBeFalse();
});

// 6. belongsTo masterPenyedia nullable
it('penyedia_id boleh null; relasi masterPenyedia mengembalikan null', function () {
    $nota = MultiNota::factory()->create(['penyedia_id' => null]);

    expect($nota->penyedia_id)->toBeNull()
        ->and($nota->masterPenyedia()->first())->toBeNull();
});

it('nota dapat ditautkan ke master_penyedia', function () {
    $penyedia = MasterPenyedia::factory()->create();
    $nota = MultiNota::factory()->create(['penyedia_id' => $penyedia->id]);

    expect($nota->masterPenyedia()->first()->id)->toBe($penyedia->id);
});

// 7. Audit
it('create, update, dan delete nota masing-masing tercatat satu entri audit', function () {
    $nota = MultiNota::factory()->create();        // created
    $nota->update(['nama_penyedia' => 'Nama Berubah']); // updated
    $nota->delete();                                // deleted (soft)

    $logs = Activity::where('subject_type', MultiNota::class)
        ->where('subject_id', $nota->id)
        ->get();

    expect($logs)->toHaveCount(3)
        ->and($logs->pluck('description')->all())->toBe(['created', 'updated', 'deleted']);
});
