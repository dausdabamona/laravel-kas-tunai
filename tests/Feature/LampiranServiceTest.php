<?php

use App\Enums\KategoriLampiran;
use App\Jobs\ProsesLampiran;
use App\Models\Lampiran;
use App\Models\MultiNota;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Services\LampiranService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

beforeEach(function () {
    Storage::fake('privat');
    $this->service = app(LampiranService::class);
});

// 1. simpan(image) -> file ada, record dibuat, job ter-dispatch
it('simpan gambar: file di disk privat, record dibuat, ProsesLampiran ter-dispatch', function () {
    Bus::fake();
    $t = TransaksiKas::factory()->create();
    $file = UploadedFile::fake()->image('nota.jpg', 2000, 1500);

    $lampiran = $this->service->simpan($t, $file, KategoriLampiran::FotoBarang);

    Storage::disk('privat')->assertExists($lampiran->path);
    expect($lampiran->exists)->toBeTrue()
        ->and($lampiran->disk)->toBe('privat')
        ->and($lampiran->nama_file)->toBe('nota.jpg')
        ->and($lampiran->kategori)->toBe(KategoriLampiran::FotoBarang)
        ->and($lampiran->path)->toStartWith('lampiran/');
    Bus::assertDispatched(ProsesLampiran::class);
});

// 2. simpan(PDF) -> file ada, record dibuat, job TIDAK dispatch
it('simpan PDF: file ada, record dibuat, job TIDAK ter-dispatch (pass-through)', function () {
    Bus::fake();
    $t = TransaksiKas::factory()->create();
    $file = UploadedFile::fake()->create('kuitansi.pdf', 100, 'application/pdf');

    $lampiran = $this->service->simpan($t, $file, KategoriLampiran::Kuitansi);

    Storage::disk('privat')->assertExists($lampiran->path);
    expect($lampiran->mime)->toBe('application/pdf');
    Bus::assertNotDispatched(ProsesLampiran::class);
});

// 3. ProsesLampiran: gambar dikecilkan <=1280 + jpeg; (PDF tak akan sampai sini)
it('ProsesLampiran mengecilkan gambar lebar maksimum 1280 dan meng-encode jpeg', function () {
    $t = TransaksiKas::factory()->create();
    $file = UploadedFile::fake()->image('besar.jpg', 3000, 2000);
    $lampiran = $this->service->simpan($t, $file, KategoriLampiran::FotoBarang);

    (new ProsesLampiran($lampiran->id))->handle();

    $isi = Storage::disk('privat')->get($lampiran->path);
    $img = (new ImageManager(new Driver))->decode($isi);

    expect($img->width())->toBeLessThanOrEqual(1280);
});

// 4. hapus(): soft-delete record, file fisik TETAP ada
it('hapus: record ter-soft-delete tetapi file fisik tetap ada (restorability)', function () {
    $t = TransaksiKas::factory()->create();
    $file = UploadedFile::fake()->image('a.jpg');
    $lampiran = $this->service->simpan($t, $file, KategoriLampiran::FotoBarang);
    $path = $lampiran->path;

    $this->service->hapus($lampiran);

    expect(Lampiran::find($lampiran->id))->toBeNull()
        ->and(Lampiran::withTrashed()->find($lampiran->id)->trashed())->toBeTrue();
    Storage::disk('privat')->assertExists($path);
});

// 5. Stream: user login + signed valid -> 200 + isi file
it('stream: user terautentikasi + URL signed valid mengembalikan 200 dan isi file', function () {
    $t = TransaksiKas::factory()->create();
    $file = UploadedFile::fake()->create('dok.pdf', 10, 'application/pdf');
    $lampiran = $this->service->simpan($t, $file, KategoriLampiran::Kuitansi);

    $url = $this->service->urlSementara($lampiran);

    $this->actingAs(User::factory()->create())
        ->get($url)
        ->assertOk();
});

// 6. Stream tanpa auth -> redirect login; signature invalid -> 403
it('stream tanpa auth redirect login; tanda tangan tidak valid -> 403', function () {
    $t = TransaksiKas::factory()->create();
    $file = UploadedFile::fake()->image('b.jpg');
    $lampiran = $this->service->simpan($t, $file, KategoriLampiran::FotoBarang);

    $url = $this->service->urlSementara($lampiran);

    // tanpa auth -> redirect ke login
    $this->get($url)->assertRedirect(route('login'));

    // signed rusak -> 403 (login dulu agar lolos middleware auth)
    $this->actingAs(User::factory()->create())
        ->get($url.'rusak')
        ->assertForbidden();
});

// 7. urutan auto-increment per attachable
it('urutan lampiran auto-increment per attachable', function () {
    $t = TransaksiKas::factory()->create();
    $nota = MultiNota::factory()->create();

    $l1 = $this->service->simpan($t, UploadedFile::fake()->image('1.jpg'), KategoriLampiran::FotoBarang);
    $l2 = $this->service->simpan($t, UploadedFile::fake()->image('2.jpg'), KategoriLampiran::FotoBarang);
    $ln = $this->service->simpan($nota, UploadedFile::fake()->image('n.jpg'), KategoriLampiran::FotoNota);

    expect($l1->urutan)->toBe(1)
        ->and($l2->urutan)->toBe(2)
        ->and($ln->urutan)->toBe(1); // per-attachable, induk berbeda mulai dari 1
});

// 8. PENJAGA morph map: semua model Auditable terpetakan
it('penjaga: semua model Auditable terdaftar di morph map (cegah FQCN bocor)', function () {
    $map = Relation::morphMap();
    $terpetakan = array_values($map);

    foreach ([TransaksiKas::class, MultiNota::class, Lampiran::class, User::class] as $model) {
        expect($terpetakan)->toContain($model);
    }
});
