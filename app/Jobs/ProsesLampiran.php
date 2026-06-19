<?php

namespace App\Jobs;

use App\Models\Lampiran;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;

/**
 * Kompresi gambar lampiran di latar (queue): kecilkan ke lebar maksimum 1280
 * dan encode ulang JPEG kualitas 70. PDF/non-gambar tidak pernah sampai sini
 * (LampiranService hanya men-dispatch untuk gambar).
 */
class ProsesLampiran implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $lampiranId) {}

    public function handle(): void
    {
        $lampiran = Lampiran::withTrashed()->find($this->lampiranId);

        if (! $lampiran || ! str_starts_with($lampiran->mime, 'image/')) {
            return;
        }

        $disk = Storage::disk($lampiran->disk);

        if (! $disk->exists($lampiran->path)) {
            return;
        }

        // Intervention v4: decode() (bukan read() seperti v3) untuk konten biner.
        $img = (new ImageManager(new Driver))->decode($disk->get($lampiran->path));
        $img->scaleDown(width: 1280); // hanya mengecilkan, rasio terjaga

        $disk->put($lampiran->path, (string) $img->encode(new JpegEncoder(quality: 70)));
    }
}
