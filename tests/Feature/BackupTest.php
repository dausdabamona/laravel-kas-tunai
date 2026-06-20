<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;

// 4. Menghasilkan file backup
it('backup:jalankan menghasilkan file di disk backup', function () {
    Storage::fake('backup');

    $this->artisan('backup:jalankan')->assertSuccessful();

    expect(Storage::disk('backup')->allFiles())->not->toBeEmpty();
});

// 5. Retensi 14 hari
it('retensi: backup lebih dari 14 hari terhapus, yang baru dipertahankan', function () {
    Storage::fake('backup');
    $lama = 'backup-'.now()->subDays(20)->format('Y-m-d-His').'.zip';
    $baru = 'backup-'.now()->subDays(2)->format('Y-m-d-His').'.zip';
    Storage::disk('backup')->put($lama, 'x');
    Storage::disk('backup')->put($baru, 'y');

    $this->artisan('backup:jalankan')->assertSuccessful();

    expect(Storage::disk('backup')->exists($lama))->toBeFalse()
        ->and(Storage::disk('backup')->exists($baru))->toBeTrue();
});

// 6. Schedule harian terdaftar
it('schedule backup harian terdaftar', function () {
    $cocok = collect(app(Schedule::class)->events())
        ->contains(fn ($e) => str_contains($e->command ?? '', 'backup:jalankan'));

    expect($cocok)->toBeTrue();
});
