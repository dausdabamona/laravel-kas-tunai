<?php

use App\Enums\KategoriLampiran;
use App\Models\Lampiran;
use App\Models\MultiNota;
use App\Models\TransaksiKas;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

/** Lampirkan satu lampiran ke induk (transaksi/nota) via morphMany. */
function lampiranUntuk($induk, array $attr = []): Lampiran
{
    $l = Lampiran::factory()->make($attr);
    $induk->lampiran()->save($l);

    return $l->refresh();
}

// 1. morphMany dari transaksi
it('transaksi memiliki lampiran via morphMany', function () {
    $t = TransaksiKas::factory()->create();
    lampiranUntuk($t, ['kategori' => KategoriLampiran::FotoBarang]);

    expect($t->lampiran()->count())->toBe(1)
        ->and($t->lampiran()->first()->kategori)->toBe(KategoriLampiran::FotoBarang);
});

// 2. morphMany dari nota (polymorphic dua induk)
it('nota memiliki lampiran via morphMany (induk kedua)', function () {
    $nota = MultiNota::factory()->create();
    lampiranUntuk($nota, ['kategori' => KategoriLampiran::FotoNota]);

    expect($nota->lampiran()->count())->toBe(1)
        ->and($nota->lampiran()->first()->kategori)->toBe(KategoriLampiran::FotoNota);
});

// 3. attachable_type = ALIAS, bukan FQCN
it('attachable_type tersimpan sebagai alias morph map, bukan FQCN', function () {
    $t = TransaksiKas::factory()->create();
    $nota = MultiNota::factory()->create();
    $lt = lampiranUntuk($t);
    $ln = lampiranUntuk($nota);

    expect(DB::table('lampiran')->where('id', $lt->id)->value('attachable_type'))->toBe('transaksi')
        ->and(DB::table('lampiran')->where('id', $ln->id)->value('attachable_type'))->toBe('nota');
});

// 4. cast enum + meta round-trip JSON
it('kategori cast enum dan meta round-trip JSON utuh', function () {
    $t = TransaksiKas::factory()->create();
    $l = lampiranUntuk($t, [
        'kategori' => KategoriLampiran::Kuitansi,
        'meta' => ['lat' => -0.8765, 'lng' => 131.2541, 'maps_url' => 'https://maps.example/x'],
    ]);

    $fresh = $l->fresh();

    expect($fresh->kategori)->toBe(KategoriLampiran::Kuitansi)
        ->and($fresh->meta)->toBeArray()
        ->and($fresh->meta['lat'])->toBe(-0.8765)
        ->and($fresh->meta['lng'])->toBe(131.2541)
        ->and($fresh->meta['maps_url'])->toBe('https://maps.example/x');
});

// 5. soft-delete + deleted_at mikrodetik
it('soft-delete lampiran: hilang normal, ada withTrashed, deleted_at berpecahan mikrodetik', function () {
    $t = TransaksiKas::factory()->create();
    $l = lampiranUntuk($t);

    $l->delete();

    $raw = DB::table('lampiran')->where('id', $l->id)->value('deleted_at');

    expect(Lampiran::find($l->id))->toBeNull()
        ->and(Lampiran::withTrashed()->find($l->id))->not->toBeNull()
        ->and($raw)->toMatch('/\.\d{6}$/'); // presisi mikrodetik
});

// 6. cascade nota -> foto_nota; restore selektif
it('cascade: hapus nota menghapus lampirannya; restore nota membangkitkan selektif', function () {
    $nota = MultiNota::factory()->create();
    $fotoA = lampiranUntuk($nota, ['kategori' => KategoriLampiran::FotoNota]);
    $fotoB = lampiranUntuk($nota, ['kategori' => KategoriLampiran::FotoNota]);

    // A dihapus manual lebih dulu (timestamp beda dari induk)
    $fotoA->delete();

    $nota->delete();   // cascade hapus B dengan timestamp induk persis
    $nota->restore();  // hanya B (deleted_at == induk) bangkit

    expect(Lampiran::withTrashed()->find($fotoA->id)->trashed())->toBeTrue()  // A tetap terhapus
        ->and(Lampiran::find($fotoB->id))->not->toBeNull()                    // B bangkit
        ->and(Lampiran::find($fotoB->id)->trashed())->toBeFalse();
});

// 7. Keputusan (b): hapus transaksi tidak menjangkau foto_nota cucu
it('keputusan b: hapus transaksi menghapus lampiran langsung + nota, TAPI foto_nota cucu tetap aktif', function () {
    $t = TransaksiKas::factory()->create();
    $fotoBarang = lampiranUntuk($t, ['kategori' => KategoriLampiran::FotoBarang]);   // langsung transaksi
    $nota = MultiNota::factory()->create(['transaksi_id' => $t->id]);
    $fotoNota = lampiranUntuk($nota, ['kategori' => KategoriLampiran::FotoNota]);    // cucu

    $t->delete();

    expect(Lampiran::withTrashed()->find($fotoBarang->id)->trashed())->toBeTrue()  // langsung -> ikut
        ->and(MultiNota::withTrashed()->find($nota->id)->trashed())->toBeTrue()    // nota anak -> ikut
        ->and(Lampiran::find($fotoNota->id))->not->toBeNull()                      // cucu -> TETAP AKTIF
        ->and(Lampiran::find($fotoNota->id)->trashed())->toBeFalse();
});

// 8. restore selektif transaksi: lampiran langsung yang dihapus manual tidak ikut bangkit
it('restore transaksi: lampiran langsung yang dihapus manual lebih dulu tidak ikut bangkit', function () {
    $t = TransaksiKas::factory()->create();
    $manual = lampiranUntuk($t, ['kategori' => KategoriLampiran::Kuitansi]);
    $cascade = lampiranUntuk($t, ['kategori' => KategoriLampiran::FotoBarang]);

    $manual->delete();
    $t->delete();
    $t->restore();

    expect(Lampiran::withTrashed()->find($manual->id)->trashed())->toBeTrue()
        ->and(Lampiran::find($cascade->id))->not->toBeNull()
        ->and(Lampiran::find($cascade->id)->trashed())->toBeFalse();
});

// 9. audit: langsung tercatat, cascade tidak
it('audit: operasi langsung lampiran tercatat (3), cascade tidak menambah', function () {
    $t = TransaksiKas::factory()->create();
    $l = lampiranUntuk($t);
    $l->update(['nama_file' => 'berubah.jpg']);
    $l->delete();

    expect(Activity::forSubject($l)->count())->toBe(3); // created, updated, deleted

    // Cascade lewat induk nota: tidak menambah audit lampiran
    $nota = MultiNota::factory()->create();
    $foto = lampiranUntuk($nota, ['kategori' => KategoriLampiran::FotoNota]);
    $nota->delete();

    expect(Activity::forSubject($foto)->count())->toBe(1)                       // hanya created
        ->and(Lampiran::withTrashed()->find($foto->id)->trashed())->toBeTrue(); // tapi tetap ter-soft-delete
});
