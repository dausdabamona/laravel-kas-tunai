<?php

use App\Enums\KategoriLampiran;
use App\Enums\Role;
use App\Livewire\BuktiPerjalanan;
use App\Models\TransaksiKas;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('privat');
    $this->actingAs(User::factory()->role(Role::Bendahara)->create());
});

// 6. Livewire: upload/list/hapus + preview signed + tautan zip
it('livewire: unggah bukti membuat Lampiran BUKTI_PD dan tampil preview signed', function () {
    $t = TransaksiKas::factory()->create();

    Livewire::test(BuktiPerjalanan::class, ['transaksi' => $t])
        ->set('berkas', UploadedFile::fake()->image('tiket.jpg'))
        ->call('unggah')
        ->assertHasNoErrors()
        ->assertSee('signature='); // thumbnail pakai signed URL

    expect($t->lampiran()->where('kategori', KategoriLampiran::BuktiPd->value)->count())->toBe(1);
});

it('livewire: hapus bukti menghilangkannya dari daftar', function () {
    $t = TransaksiKas::factory()->create();

    $comp = Livewire::test(BuktiPerjalanan::class, ['transaksi' => $t])
        ->set('berkas', UploadedFile::fake()->image('tiket.jpg'))
        ->call('unggah');

    $id = $t->lampiran()->where('kategori', KategoriLampiran::BuktiPd->value)->first()->id;

    $comp->call('hapus', $id);

    expect($t->lampiran()->where('kategori', KategoriLampiran::BuktiPd->value)->count())->toBe(0);
});

it('livewire: tautan unduh zip muncul saat ada bukti', function () {
    $t = TransaksiKas::factory()->create();

    Livewire::test(BuktiPerjalanan::class, ['transaksi' => $t])
        ->set('berkas', UploadedFile::fake()->image('tiket.jpg'))
        ->call('unggah')
        ->assertSee('bukti-zip');
});
