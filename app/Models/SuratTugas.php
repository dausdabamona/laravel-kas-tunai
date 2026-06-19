<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasMicrosecondTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Surat Tugas / Perjalanan Dinas.
 *
 * Tertaut ke baris porsi pelaksana (transaksi_id = baris PD_POKOK). Ikut cascade
 * soft-delete dari baris pokok (lihat TransaksiKas::cascadeRelations).
 */
class SuratTugas extends Model
{
    use Auditable, HasMicrosecondTimestamps;

    protected $table = 'surat_tugas';

    protected $fillable = [
        'transaksi_id',
        'nomor_surat',
        'dasar',
        'maksud',
        'angkutan',
        'tempat_berangkat',
        'tempat_tujuan',
        'tgl_berangkat',
        'tgl_kembali',
        'lama_hari',
        'akun',
        'jenis',
        'pegawai',
        'biaya_total',
        'sumber_pelaksana',
        'sumber_bendahara',
        'ttd_ppk',
        'nip_ppk',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'tgl_berangkat' => 'date',
            'tgl_kembali' => 'date',
            'lama_hari' => 'integer',
            'biaya_total' => 'integer',
            'pegawai' => 'array',
        ];
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(TransaksiKas::class, 'transaksi_id');
    }
}
