<?php

namespace App\Services;

use App\Enums\KategoriLampiran;
use App\Jobs\ProsesLampiran;
use App\Models\Lampiran;
use App\Models\TransaksiKas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use ZipArchive;

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

    /**
     * Susun ZIP berisi seluruh bukti PD AKTIF milik transaksi (entri bernama
     * "{urutan}_{nama_file}"). Disimpan ke disk privat (tmp), kembalikan path
     * relatif. ZipArchive native — tanpa dependensi tambahan.
     */
    public function zipBukti(TransaksiKas $t): string
    {
        $disk = Storage::disk('privat');

        $bukti = $t->lampiran()
            ->where('kategori', KategoriLampiran::BuktiPd->value)
            ->orderBy('urutan')
            ->get();

        $zipRel = 'tmp/bukti-'.Str::ulid().'.zip';
        $zipAbs = $disk->path($zipRel);
        @mkdir(dirname($zipAbs), 0775, true);

        $zip = new ZipArchive;
        $zip->open($zipAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($bukti as $l) {
            $abs = $disk->path($l->path);
            if (is_file($abs)) {
                $zip->addFile($abs, $l->urutan.'_'.$l->nama_file);
            }
        }

        $zip->close();

        return $zipRel;
    }

    /**
     * URL bertanda tangan & kedaluwarsa untuk mengunduh ZIP bukti transaksi.
     */
    public function urlZipBukti(TransaksiKas $t): string
    {
        return URL::temporarySignedRoute(
            'lampiran.bukti-zip',
            now()->addMinutes(10),
            ['transaksi' => $t->id],
        );
    }

    private function urutanBerikutnya(Model $attachable): int
    {
        return (int) $attachable->lampiran()->max('urutan') + 1;
    }
}
