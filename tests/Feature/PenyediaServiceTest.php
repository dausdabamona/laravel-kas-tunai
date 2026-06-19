<?php

use App\Models\MasterPenyedia;
use App\Services\PenyediaService;

beforeEach(function () {
    $this->penyedia = app(PenyediaService::class);
});

it('membuat penyedia baru dengan frekuensi 1 dan terakhir_digunakan terisi', function () {
    $p = $this->penyedia->simpanAtauUpdate([
        'nama' => 'CV Maju Jaya',
        'npwp' => '01.234.567.8-901.000',
        'alamat' => 'Jl. Merdeka No. 1',
    ]);

    expect(MasterPenyedia::count())->toBe(1)
        ->and($p->frekuensi)->toBe(1)
        ->and($p->terakhir_digunakan)->not->toBeNull()
        ->and($p->nama)->toBe('CV Maju Jaya');
});

it('simpan ulang nama sama (case-insensitive) tidak membuat baris baru, frekuensi naik, data ter-update', function () {
    $this->penyedia->simpanAtauUpdate([
        'nama' => 'CV Maju',
        'npwp' => '11.111.111.1-111.000',
        'alamat' => 'Alamat Lama',
    ]);

    $kedua = $this->penyedia->simpanAtauUpdate([
        'nama' => 'cv maju',                 // beda kapitalisasi
        'npwp' => '99.999.999.9-999.000',
        'alamat' => 'Alamat Baru',
    ]);

    expect(MasterPenyedia::count())->toBe(1)
        ->and($kedua->frekuensi)->toBe(2)
        ->and($kedua->npwp)->toBe('99.999.999.9-999.000')
        ->and($kedua->alamat)->toBe('Alamat Baru');
});

it('mencegah duplikat lintas-kapitalisasi (bukan hanya andalkan unique exact-case)', function () {
    $this->penyedia->simpanAtauUpdate(['nama' => 'Toko Sumber Rejeki']);
    $this->penyedia->simpanAtauUpdate(['nama' => 'TOKO SUMBER REJEKI']);
    $this->penyedia->simpanAtauUpdate(['nama' => 'toko sumber rejeki']);

    expect(MasterPenyedia::count())->toBe(1)
        ->and(MasterPenyedia::first()->frekuensi)->toBe(3);
});

it('tidak menimpa npwp/alamat lama dengan nilai kosong saat simpan ulang', function () {
    $this->penyedia->simpanAtauUpdate([
        'nama' => 'PT Tetap',
        'npwp' => '22.222.222.2-222.000',
        'alamat' => 'Alamat Asli',
    ]);

    // Simpan ulang tanpa npwp/alamat — data lama harus bertahan
    $p = $this->penyedia->simpanAtauUpdate(['nama' => 'PT Tetap']);

    expect($p->npwp)->toBe('22.222.222.2-222.000')
        ->and($p->alamat)->toBe('Alamat Asli')
        ->and($p->frekuensi)->toBe(2);
});

it('cari berdasarkan potongan nama (LIKE) mengembalikan yang cocok', function () {
    $this->penyedia->simpanAtauUpdate(['nama' => 'CV Maju Jaya']);
    $this->penyedia->simpanAtauUpdate(['nama' => 'CV Mundur Teratur']);

    $hasil = $this->penyedia->cari('maju');

    expect($hasil)->toHaveCount(1)
        ->and($hasil->first()->nama)->toBe('CV Maju Jaya');
});

it('cari berdasarkan NPWP juga cocok', function () {
    $this->penyedia->simpanAtauUpdate([
        'nama' => 'PT Pajak Benar',
        'npwp' => '09.876.543.2-100.000',
    ]);
    $this->penyedia->simpanAtauUpdate(['nama' => 'CV Lain', 'npwp' => '00.000.000.0-000.000']);

    $hasil = $this->penyedia->cari('876.543');

    expect($hasil)->toHaveCount(1)
        ->and($hasil->first()->nama)->toBe('PT Pajak Benar');
});

it('getAll mengurutkan berdasarkan frekuensi desc (yang sering dipakai dulu)', function () {
    // "Sering" dipakai 3x, "Jarang" 1x
    $this->penyedia->simpanAtauUpdate(['nama' => 'Penyedia Jarang']);
    foreach (range(1, 3) as $_) {
        $this->penyedia->simpanAtauUpdate(['nama' => 'Penyedia Sering']);
    }

    $semua = $this->penyedia->getAll();

    expect($semua->first()->nama)->toBe('Penyedia Sering')
        ->and($semua->first()->frekuensi)->toBe(3)
        ->and($semua->last()->nama)->toBe('Penyedia Jarang');
});
