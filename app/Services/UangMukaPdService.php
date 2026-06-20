<?php

namespace App\Services;

use App\Enums\JenisTransaksi;
use App\Enums\Sumber;
use App\Models\SuratTugas;
use App\Models\TransaksiKas;
use App\Models\UangMukaPd;
use App\Support\Periode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Catat pemberian uang muka PD (tunai/transfer) + baris KELUAR TransaksiKas.
 *
 * Baris kas mengikuti metode (tunai/bank) dan ref_group surat tugas sehingga
 * SALDO kas tunai/bank langsung berkurang. Σ uang muka = "telah dibayar" pada
 * kuitansi rampung (lihat RincianPdService).
 */
class UangMukaPdService
{
    /**
     * @param  array{pegawai_index?: int, tanggal: string, jumlah: int, metode: string, keterangan?: ?string}  $d
     */
    public function catat(SuratTugas $st, array $d): UangMukaPd
    {
        if (Periode::terkunci($d['tanggal'])) {
            throw new AuthorizationException('Periode terkunci: tidak dapat mencatat uang muka pada periode ini.');
        }

        return DB::transaction(function () use ($st, $d) {
            $pegawaiIndex = (int) ($d['pegawai_index'] ?? 0);
            $metode = Sumber::from($d['metode']);
            $namaPegawai = $st->pegawai[$pegawaiIndex]['nama'] ?? '';

            $uangMuka = UangMukaPd::create([
                'surat_tugas_id' => $st->id,
                'pegawai_index' => $pegawaiIndex,
                'urutan' => (int) $st->uangMuka()->max('urutan') + 1,
                'tanggal' => $d['tanggal'],
                'jumlah' => $d['jumlah'],
                'metode' => $metode,
                'keterangan' => $d['keterangan'] ?? null,
                'dibuat_oleh' => auth()->id(),
            ]);

            $keluar = TransaksiKas::create([
                'tanggal' => $d['tanggal'],
                'kegiatan' => 'Uang muka PD: '.($st->transaksi?->kegiatan ?? $st->maksud),
                'keterangan' => 'Uang muka '.$namaPegawai.' (SPD '.$st->nomor_surat.')',
                'debet' => 0,
                'kredit' => $d['jumlah'],
                'sumber' => $metode,
                'jenis' => JenisTransaksi::PdUangMuka,
                'parent_id' => $st->transaksi_id,
                'ref_group' => $st->transaksi?->ref_group,
            ]);

            $uangMuka->updateQuietly(['ref_kas_id' => $keluar->id]);

            return $uangMuka;
        });
    }
}
