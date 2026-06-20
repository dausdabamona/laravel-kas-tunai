<?php

namespace App\Services;

use App\Enums\KategoriLampiran;
use App\Models\Lampiran;
use App\Models\TransaksiKas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use ZipArchive;

class LampiranService
{
    /**
     * Simpan file ke disk privat + buat record Lampiran.
     *
     * Gambar dikompres SINKRON sebelum disimpan (lebar maks 1280, JPEG q70) —
     * tak ada job/queue. PDF/non-gambar pass-through. attachable = transaksi/nota.
     */
    public function simpan(Model $attachable, UploadedFile $file, KategoriLampiran $kategori, array $meta = []): Lampiran
    {
        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension();
        $path = sprintf('lampiran/%s/%s/%s.%s', now()->year, $kategori->value, Str::ulid(), $ext);
        $mime = $file->getMimeType() ?? $file->getClientMimeType();

        $isi = str_starts_with((string) $mime, 'image/')
            ? $this->kompresGambar($file->get())   // kompres sinkron
            : $file->get();                          // PDF/non-gambar pass-through

        Storage::disk('privat')->put($path, $isi);

        return $attachable->lampiran()->create([
            'kategori' => $kategori,
            'urutan' => $this->urutanBerikutnya($attachable),
            'disk' => 'privat',
            'path' => $path,
            'nama_file' => $file->getClientOriginalName(),
            'mime' => $mime,
            'meta' => $meta ?: null,
        ]);
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
     * Data URI base64 (mis. data:image/jpeg;base64,...) untuk MENANAM gambar
     * langsung ke dokumen cetak (SPJ) — agar tampil di print/Save-as-PDF tanpa
     * bergantung pada signed URL/sesi. Kembalikan null bila berkas hilang.
     */
    public function dataUri(Lampiran $lampiran): ?string
    {
        $disk = Storage::disk($lampiran->disk);

        if (! $disk->exists($lampiran->path)) {
            return null;
        }

        $mime = $lampiran->mime ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($disk->get($lampiran->path));
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

    /**
     * Kompresi gambar sinkron: kecilkan ke lebar maks 1280, encode JPEG q70.
     * Intervention v4 (decode/encode, terverifikasi sejak 2.4).
     */
    private function kompresGambar(string $contents): string
    {
        $img = (new ImageManager(new Driver))->decode($contents);
        $img->scaleDown(width: 1280);

        return (string) $img->encode(new JpegEncoder(quality: 70));
    }

    private function urutanBerikutnya(Model $attachable): int
    {
        return (int) $attachable->lampiran()->max('urutan') + 1;
    }
}
