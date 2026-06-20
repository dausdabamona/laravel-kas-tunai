<?php

use App\Enums\KategoriLampiran;
use App\Enums\Role;
use App\Enums\StatusSpj;
use App\Livewire\Nota\Kelola;
use App\Models\MasterPenyedia;
use App\Models\MultiNota;
use App\Models\TransaksiKas;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('privat');
    $this->actingAs(User::factory()->role(Role::Bendahara)->create()); // akses penuh
});

// 1
it('render menampilkan nota transaksi, total, dan badge status', function () {
    $t = TransaksiKas::factory()->create(['kredit' => 1_000_000, 'nilai_spby' => 0, 'status_spj' => StatusSpj::Belum]);
    MultiNota::factory()->create(['transaksi_id' => $t->id, 'nama_penyedia' => 'CV Sumber Rejeki', 'nominal' => 400_000]);

    Livewire::test(Kelola::class, ['transaksi' => $t])
        ->assertOk()
        ->assertSee('CV Sumber Rejeki')
        ->assertSee('Belum SPJ');
});

// 2
it('tambah nota: tersimpan, recalc otomatis lunas, penyedia tercatat', function () {
    $t = TransaksiKas::factory()->create(['kredit' => 1_000_000, 'nilai_spby' => 0, 'status_spj' => StatusSpj::Belum]);

    Livewire::test(Kelola::class, ['transaksi' => $t])
        ->set('nama_penyedia', 'CV Maju Jaya')
        ->set('npwp_penyedia', '01.111.111.1-111.000')
        ->set('alamat_penyedia', 'Jl. Uji')
        ->set('nominal', 1_000_000)
        ->set('tgl_nota', '2026-06-19')
        ->call('simpanNota')
        ->assertHasNoErrors();

    expect($t->nota()->count())->toBe(1)
        ->and($t->fresh()->status_spj)->toBe(StatusSpj::Lunas)
        ->and(MasterPenyedia::where('nama', 'CV Maju Jaya')->exists())->toBeTrue()
        ->and($t->nota()->first()->penyedia_id)->not->toBeNull();
});

// 3
it('autocomplete penyedia: cari memberi kandidat; pilih mengisi npwp & alamat', function () {
    MasterPenyedia::factory()->create(['nama' => 'CV Maju Jaya', 'npwp' => '09.999.999.9-999.000', 'alamat' => 'Jl. Lama']);
    $t = TransaksiKas::factory()->create();

    $comp = Livewire::test(Kelola::class, ['transaksi' => $t])
        ->set('nama_penyedia', 'maju')
        ->call('cariPenyedia');

    expect($comp->get('kandidatPenyedia'))->toHaveCount(1);

    $comp->call('pilihPenyedia', MasterPenyedia::first()->id)
        ->assertSet('npwp_penyedia', '09.999.999.9-999.000')
        ->assertSet('alamat_penyedia', 'Jl. Lama');
});

// 4
it('edit nota: tersimpan dan recalc menyesuaikan', function () {
    $t = TransaksiKas::factory()->create(['kredit' => 1_000_000, 'nilai_spby' => 0, 'status_spj' => StatusSpj::Belum]);
    $nota = MultiNota::factory()->create(['transaksi_id' => $t->id, 'nominal' => 500_000, 'nama_penyedia' => 'CV Awal']);

    Livewire::test(Kelola::class, ['transaksi' => $t])
        ->call('editNota', $nota->id)
        ->assertSet('nominal', 500_000)
        ->set('nominal', 1_000_000)
        ->call('simpanNota')
        ->assertHasNoErrors();

    expect($nota->fresh()->nominal)->toBe(1_000_000)
        ->and($t->fresh()->status_spj)->toBe(StatusSpj::Lunas);
});

// 5
it('hapus nota: soft-delete, hilang dari list, recalc balik ke belum', function () {
    $t = TransaksiKas::factory()->create(['kredit' => 1_000_000, 'nilai_spby' => 0]);
    $nota = MultiNota::factory()->create(['transaksi_id' => $t->id, 'nominal' => 1_000_000, 'nama_penyedia' => 'CV Hapus']);
    expect($t->fresh()->status_spj)->toBe(StatusSpj::Lunas);

    Livewire::test(Kelola::class, ['transaksi' => $t])
        ->call('hapusNota', $nota->id)
        ->assertDontSee('CV Hapus');

    expect($t->nota()->count())->toBe(0)
        ->and($t->fresh()->status_spj)->toBe(StatusSpj::Belum);
});

// 6
it('upload foto nota dengan lat/lng membuat Lampiran FOTO_NOTA bermeta GPS', function () {
    $t = TransaksiKas::factory()->create();
    $nota = MultiNota::factory()->create(['transaksi_id' => $t->id]);

    Livewire::test(Kelola::class, ['transaksi' => $t])
        ->set('lat', -0.8765)
        ->set('lng', 131.2541)
        ->set('maps_url', 'https://maps.example/x')
        ->set('fotoNota', UploadedFile::fake()->image('nota.jpg'))
        ->call('simpanFotoNota', $nota->id)
        ->assertHasNoErrors();

    $lamp = $nota->lampiran()->first();
    expect($lamp)->not->toBeNull()
        ->and($lamp->kategori)->toBe(KategoriLampiran::FotoNota)
        ->and($lamp->meta['lat'])->toBe(-0.8765)
        ->and($lamp->meta['lng'])->toBe(131.2541);
});

// 6b — salah upload bisa dihapus
it('hapus foto: foto nota yang salah unggah dapat dihapus (soft-delete)', function () {
    $t = TransaksiKas::factory()->create();
    $nota = MultiNota::factory()->create(['transaksi_id' => $t->id]);

    $component = Livewire::test(Kelola::class, ['transaksi' => $t])
        ->set('fotoNota', UploadedFile::fake()->image('salah.jpg'))
        ->call('simpanFotoNota', $nota->id);

    $foto = $nota->lampiran()->first();
    expect($foto)->not->toBeNull();

    $component->call('hapusFoto', $foto->id);

    expect($nota->lampiran()->count())->toBe(0);
});

// 7 — foto barang BERPASANGAN dengan nota (menempel ke nota, bukan transaksi)
it('upload foto barang membuat Lampiran FOTO_BARANG menempel ke NOTA (berpasangan)', function () {
    $t = TransaksiKas::factory()->create();
    $nota = MultiNota::factory()->create(['transaksi_id' => $t->id]);

    Livewire::test(Kelola::class, ['transaksi' => $t])
        ->set('fotoBarang', UploadedFile::fake()->image('barang.jpg'))
        ->call('simpanFotoBarang', $nota->id)
        ->assertHasNoErrors();

    $lamp = $nota->lampiran()->first();
    expect($lamp)->not->toBeNull()
        ->and($lamp->kategori)->toBe(KategoriLampiran::FotoBarang)
        ->and($t->lampiran()->count())->toBe(0); // tidak lagi di level transaksi
});

// 8
it('preview foto memakai URL signed (route lampiran.stream bertanda), bukan path mentah', function () {
    $t = TransaksiKas::factory()->create();
    $nota = MultiNota::factory()->create(['transaksi_id' => $t->id]);

    Livewire::test(Kelola::class, ['transaksi' => $t])
        ->set('fotoBarang', UploadedFile::fake()->image('barang.jpg'))
        ->call('simpanFotoBarang', $nota->id)
        ->assertSee('/lampiran/')
        ->assertSee('signature=');
});

// 9a
it('policy: operator pada transaksi LUNAS (bukan draft) ditolak menambah nota', function () {
    $operator = User::factory()->role(Role::Operator)->create();
    $t = TransaksiKas::factory()->create(['status_spj' => StatusSpj::Lunas, 'kredit' => 1_000_000]);

    Livewire::actingAs($operator)
        ->test(Kelola::class, ['transaksi' => $t])
        ->set('nama_penyedia', 'CV X')
        ->set('nominal', 100_000)
        ->call('simpanNota')
        ->assertForbidden();
});

// 9b
it('policy: periode terkunci memblok bendahara menambah nota', function () {
    config()->set('kas.periode_terkunci_hingga', '2026-12-31');
    $t = TransaksiKas::factory()->create(['tanggal' => '2026-06-10', 'status_spj' => StatusSpj::Belum, 'kredit' => 1_000_000]);

    Livewire::test(Kelola::class, ['transaksi' => $t])
        ->set('nama_penyedia', 'CV Y')
        ->set('nominal', 100_000)
        ->call('simpanNota')
        ->assertForbidden();
});
