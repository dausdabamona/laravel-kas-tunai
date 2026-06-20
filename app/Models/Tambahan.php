<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasMicrosecondTimestamps;
use App\Support\Cascade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Tambahan kekurangan dana belanja (bendahara menambah saat nota > uang muka).
 *
 * Cermin Pengembalian dengan arah berlawanan: setiap record tertaut 1:1 ke baris
 * KELUAR TransaksiKas (ref_keluar_id) yang dibuat otomatis oleh TambahanService.
 * Menghapus/restore meng-cascade baris keluar itu (timestamp-match).
 *
 * Catatan: tambahan TIDAK memengaruhi status_spj (status_spj murni dari nota vs
 * target). Tambahan hanya relevan pada Status Rekonsiliasi (selisih kas).
 */
class Tambahan extends Model
{
    use Auditable, HasMicrosecondTimestamps;

    protected $table = 'tambahan';

    protected $fillable = [
        'transaksi_id',
        'urutan',
        'tanggal',
        'jumlah',
        'keterangan',
        'dibuat_oleh',
        'ref_keluar_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleted(function (self $t) {
            // Cascade 1:1 ke baris keluar tertaut (timestamp-match) — tak memicu event.
            if (! $t->isForceDeleting() && $t->ref_keluar_id) {
                Cascade::softDelete(TransaksiKas::whereKey($t->ref_keluar_id), $t->deleted_at);
            }
        });

        static::restoring(function (self $t) {
            if ($t->ref_keluar_id) {
                Cascade::restore(TransaksiKas::whereKey($t->ref_keluar_id), $t->deleted_at);
            }
        });
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(TransaksiKas::class, 'transaksi_id');
    }

    public function refKeluar(): BelongsTo
    {
        return $this->belongsTo(TransaksiKas::class, 'ref_keluar_id');
    }

    public function lampiran(): MorphMany
    {
        return $this->morphMany(Lampiran::class, 'attachable');
    }
}
