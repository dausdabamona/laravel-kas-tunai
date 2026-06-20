<?php

namespace App\Services;

use App\Enums\JenisTransaksi;
use App\Models\Pengembalian;
use App\Models\TransaksiKas;
use App\Support\Periode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class PengembalianService
{
    /**
     * Catat pengembalian sebagian dana + buat baris masuk TransaksiKas tertaut.
     *
     * Baris masuk mengikuti SUMBER induk (beda dari GAS yang selalu TUNAI) —
     * lebih konsisten dengan buku per-sumber.
     *
     * @param  array{tanggal: string, jumlah: int, keterangan?: ?string}  $d
     */
    public function catat(TransaksiKas $induk, array $d): Pengembalian
    {
        if (Periode::terkunci($d['tanggal'])) {
            throw new AuthorizationException('Periode terkunci: tidak dapat mencatat pengembalian pada periode ini.');
        }

        return DB::transaction(function () use ($induk, $d) {
            $pengembalian = Pengembalian::create([
                'transaksi_id' => $induk->id,
                'urutan' => (int) $induk->pengembalian()->max('urutan') + 1,
                'tanggal' => $d['tanggal'],
                'jumlah' => $d['jumlah'],
                'keterangan' => $d['keterangan'] ?? null,
                'dibuat_oleh' => auth()->id(),
            ]);

            $masuk = TransaksiKas::create([
                'tanggal' => $d['tanggal'],
                'kegiatan' => 'Pengembalian: '.$induk->kegiatan,
                'keterangan' => 'Otomatis dari pengembalian transaksi '.$induk->no,
                'debet' => $d['jumlah'],
                'kredit' => 0,
                'sumber' => $induk->sumber,
                'jenis' => JenisTransaksi::Pengembalian,
                'parent_id' => $induk->id,
            ]);

            // updateQuietly: set tautan tanpa memicu audit 'updated' & recalc ulang.
            $pengembalian->updateQuietly(['ref_masuk_id' => $masuk->id]);

            return $pengembalian;
        });
    }
}
