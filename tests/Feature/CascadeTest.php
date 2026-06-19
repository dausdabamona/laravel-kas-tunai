<?php

use App\Models\MultiNota;
use App\Models\TransaksiKas;
use App\Support\Cascade;
use Illuminate\Support\Carbon;

it('Cascade::softDelete menghapus-lunak baris aktif dengan timestamp diberikan', function () {
    $t = TransaksiKas::factory()->create();
    MultiNota::factory()->count(2)->create(['transaksi_id' => $t->id]);
    $ts = Carbon::parse('2026-06-19 10:00:00.123456');

    Cascade::softDelete($t->nota(), $ts);

    // Bulk update mengikat Carbon di presisi DETIK (mikrodetik dipangkas) —
    // asimetri inilah yang membuat restore selektif bekerja vs hapus-manual (mikro).
    expect($t->nota()->count())->toBe(0)
        ->and(MultiNota::withTrashed()->where('transaksi_id', $t->id)->count())->toBe(2)
        ->and(MultiNota::withTrashed()->where('transaksi_id', $t->id)->first()->deleted_at->format('Y-m-d H:i:s'))
        ->toBe('2026-06-19 10:00:00');
});

it('Cascade::restore membangkitkan HANYA baris ber-deleted_at cocok (selektif)', function () {
    $t = TransaksiKas::factory()->create();
    $cocok = MultiNota::factory()->create(['transaksi_id' => $t->id]);
    $beda = MultiNota::factory()->create(['transaksi_id' => $t->id]);

    $tsCocok = Carbon::parse('2026-06-19 10:00:00.111111');
    // Dua grup timestamp berbeda.
    Cascade::softDelete(MultiNota::whereKey($cocok->id), $tsCocok);
    Cascade::softDelete(MultiNota::whereKey($beda->id), Carbon::parse('2026-06-19 09:00:00.999999'));

    Cascade::restore(MultiNota::query(), $tsCocok);

    expect(MultiNota::find($cocok->id))->not->toBeNull()                 // cocok -> bangkit
        ->and(MultiNota::find($cocok->id)->trashed())->toBeFalse()
        ->and(MultiNota::withTrashed()->find($beda->id)->trashed())->toBeTrue(); // beda -> tetap terhapus
});
