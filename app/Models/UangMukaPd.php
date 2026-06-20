<?php

namespace App\Models;

use App\Enums\Sumber;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasMicrosecondTimestamps;
use App\Support\Cascade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uang muka perjalanan dinas per pegawai (tunai/transfer).
 *
 * Cermin pola Tambahan: tiap record tertaut 1:1 ke baris KELUAR TransaksiKas
 * (ref_kas_id) yang dibuat otomatis oleh UangMukaPdService. Menghapus/restore
 * meng-cascade baris kas itu (timestamp-match) sehingga saldo ikut pulih.
 */
class UangMukaPd extends Model
{
    use Auditable, HasMicrosecondTimestamps;

    protected $table = 'uang_muka_pd';

    protected $fillable = [
        'surat_tugas_id',
        'pegawai_index',
        'urutan',
        'tanggal',
        'jumlah',
        'metode',
        'keterangan',
        'dibuat_oleh',
        'ref_kas_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'pegawai_index' => 'integer',
            'jumlah' => 'integer',
            'metode' => Sumber::class,
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (self $u) {
            if (! $u->isForceDeleting() && $u->ref_kas_id) {
                Cascade::softDelete(TransaksiKas::whereKey($u->ref_kas_id), $u->deleted_at);
            }
        });

        static::restoring(function (self $u) {
            if ($u->ref_kas_id) {
                Cascade::restore(TransaksiKas::whereKey($u->ref_kas_id), $u->deleted_at);
            }
        });
    }

    public function suratTugas(): BelongsTo
    {
        return $this->belongsTo(SuratTugas::class, 'surat_tugas_id');
    }

    public function refKas(): BelongsTo
    {
        return $this->belongsTo(TransaksiKas::class, 'ref_kas_id');
    }
}
