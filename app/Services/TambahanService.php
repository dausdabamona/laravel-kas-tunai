<?php

namespace App\Services;

use App\Enums\JenisTransaksi;
use App\Models\Tambahan;
use App\Models\TransaksiKas;
use App\Support\Periode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Catat tambahan kekurangan + buat baris KELUAR TransaksiKas tertaut.
 *
 * Cermin PengembalianService dengan arah berlawanan: pengembalian membuat baris
 * MASUK (debet), tambahan membuat baris KELUAR (kredit) — kas bendahara berkurang
 * karena menalangi kekurangan belanja pelaksana. Baris keluar mengikuti SUMBER
 * induk agar buku per-sumber konsisten.
 */
class TambahanService
{
    /**
     * @param  array{tanggal: string, jumlah: int, keterangan?: ?string}  $d
     */
    public function catat(TransaksiKas $induk, array $d): Tambahan
    {
        if (Periode::terkunci($d['tanggal'])) {
            throw new AuthorizationException('Periode terkunci: tidak dapat mencatat tambahan pada periode ini.');
        }

        return DB::transaction(function () use ($induk, $d) {
            $tambahan = Tambahan::create([
                'transaksi_id' => $induk->id,
                'urutan' => (int) $induk->tambahan()->max('urutan') + 1,
                'tanggal' => $d['tanggal'],
                'jumlah' => $d['jumlah'],
                'keterangan' => $d['keterangan'] ?? null,
                'dibuat_oleh' => auth()->id(),
            ]);

            $keluar = TransaksiKas::create([
                'tanggal' => $d['tanggal'],
                'kegiatan' => 'Tambahan kekurangan: '.$induk->kegiatan,
                'keterangan' => 'Otomatis dari tambahan transaksi '.$induk->no,
                'debet' => 0,
                'kredit' => $d['jumlah'],
                'sumber' => $induk->sumber,
                'jenis' => JenisTransaksi::Tambahan,
                'parent_id' => $induk->id,
            ]);

            // updateQuietly: set tautan tanpa memicu audit 'updated'.
            $tambahan->updateQuietly(['ref_keluar_id' => $keluar->id]);

            return $tambahan;
        });
    }
}
