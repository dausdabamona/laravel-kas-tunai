<?php

use App\Enums\KategoriLampiran;
use App\Enums\Role;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Services\LampiranService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('privat');
    $this->service = app(LampiranService::class);
});

// 1. upload bukti image -> BUKTI_PD (terkompres sinkron)
it('upload bukti gambar: Lampiran BUKTI_PD tersimpan', function () {
    $t = TransaksiKas::factory()->create();

    $l = $this->service->simpan($t, UploadedFile::fake()->image('tiket.jpg'), KategoriLampiran::BuktiPd);

    expect($l->kategori)->toBe(KategoriLampiran::BuktiPd);
    Storage::disk('privat')->assertExists($l->path);
});

// 2. upload bukti PDF -> pass-through
it('upload bukti PDF: pass-through tersimpan', function () {
    $t = TransaksiKas::factory()->create();

    $l = $this->service->simpan($t, UploadedFile::fake()->create('boarding.pdf', 50, 'application/pdf'), KategoriLampiran::BuktiPd);

    expect($l->mime)->toBe('application/pdf');
    Storage::disk('privat')->assertExists($l->path);
});

// 3. zipBukti hanya bukti AKTIF, entri bernama jelas
it('zipBukti: berisi bukti AKTIF dengan entri {urutan}_{nama}; yang terhapus dilewati', function () {
    $t = TransaksiKas::factory()->create();
    $b1 = $this->service->simpan($t, UploadedFile::fake()->image('a.jpg'), KategoriLampiran::BuktiPd);
    $b2 = $this->service->simpan($t, UploadedFile::fake()->image('b.jpg'), KategoriLampiran::BuktiPd);
    $b2->delete(); // soft-delete -> tidak masuk zip

    $zipRel = $this->service->zipBukti($t);
    $abs = Storage::disk('privat')->path($zipRel);

    $zip = new ZipArchive;
    $zip->open($abs);
    $entri = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entri[] = $zip->getNameIndex($i);
    }
    $zip->close();

    expect($entri)->toBe(['1_a.jpg']);
});

// 4. unduh zip via signed route
it('unduh zip: signed + auth + authorize 200; tanpa auth redirect; signature rusak 403', function () {
    $t = TransaksiKas::factory()->create();
    $this->service->simpan($t, UploadedFile::fake()->image('a.jpg'), KategoriLampiran::BuktiPd);

    $url = $this->service->urlZipBukti($t);

    // tanpa auth (guest) lebih dulu -> redirect login
    $this->get($url)->assertRedirect(route('login'));

    // signed valid + auth -> 200
    $this->actingAs(User::factory()->role(Role::Bendahara)->create())->get($url)->assertOk();

    // signature rusak -> 403
    $this->get($url.'rusak')->assertForbidden();
});

// 5. hapus bukti -> file fisik tetap
it('hapus bukti: record soft-deleted, file fisik tetap (restorability)', function () {
    $t = TransaksiKas::factory()->create();
    $l = $this->service->simpan($t, UploadedFile::fake()->image('a.jpg'), KategoriLampiran::BuktiPd);
    $path = $l->path;

    $this->service->hapus($l);

    Storage::disk('privat')->assertExists($path);
});
