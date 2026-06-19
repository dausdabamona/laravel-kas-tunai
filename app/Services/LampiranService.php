<?php

namespace App\Services;

use App\Enums\KategoriLampiran;
use App\Jobs\ProsesLampiran;
use App\Models\Lampiran;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class LampiranService
{
    /**
     * Simpan file mentah ke disk privat + buat record Lampiran.
     *
     * File gambar didispatch ke ProsesLampiran (kompresi queue). PDF/non-gambar
     * pass-through (tidak dikompresi). attachable = transaksi atau nota.
     */
    public function simpan(Model $attachable, UploadedFile $file, KategoriLampiran $kategori, array $meta = []): Lampiran
    {
        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension();
        $path = sprintf('lampiran/%s/%s/%s.%s', now()->year, $kategori->value, Str::ulid(), $ext);

        // Simpan file MENTAH dulu (kompresi gambar menyusul di queue).
        Storage::disk('privat')->put($path, $file->get());

        $lampiran = $attachable->lampiran()->create([
            'kategori' => $kategori,
            'urutan' => $this->urutanBerikutnya($attachable),
            'disk' => 'privat',
            'path' => $path,
            'nama_file' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType() ?? $file->getClientMimeType(),
            'meta' => $meta ?: null,
        ]);

        if (str_starts_with((string) $lampiran->mime, 'image/')) {
            ProsesLampiran::dispatch($lampiran->id);
        }

        return $lampiran;
    }

    /**
     * Soft-delete record SAJA — file fisik dipertahankan agar cascade restore
     * tetap dapat membangkitkan lampiran. Pembuangan file permanen dilakukan
     * saat purge terjadwal (fase berikutnya), bukan di sini.
     */
    public function hapus(Lampiran $lampiran): void
    {
        $lampiran->delete();
    }

    /**
     * URL bertanda tangan & kedaluwarsa (10 menit) ke route stream privat.
     * Driver local tidak mendukung Storage::temporaryUrl(), jadi pakai signed route.
     */
    public function urlSementara(Lampiran $lampiran): string
    {
        return URL::temporarySignedRoute(
            'lampiran.stream',
            now()->addMinutes(10),
            ['lampiran' => $lampiran->id],
        );
    }

    private function urutanBerikutnya(Model $attachable): int
    {
        return (int) $attachable->lampiran()->max('urutan') + 1;
    }
}
