<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasMicrosecondTimestamps;
use App\Services\NotaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengembalian sebagian dana belanja (sisa dikembalikan ke kas).
 *
 * Setiap record tertaut 1:1 ke baris masuk TransaksiKas (ref_masuk_id) yang
 * dibuat otomatis oleh PengembalianService. Menghapus/restore pengembalian
 * meng-cascade baris masuk itu (timestamp-match) dan memicu recalc status_spj.
 */
class Pengembalian extends Model
{
    use Auditable, HasMicrosecondTimestamps;

    protected $table = 'pengembalian';

    protected $fillable = [
        'transaksi_id',
        'urutan',
        'tanggal',
        'jumlah',
        'keterangan',
        'dibuat_oleh',
        'ref_masuk_id',
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
        $recalc = fn (self $p) => optional($p->transaksi, fn ($t) => app(NotaService::class)->recalc($t));

        static::saved($recalc);

        static::deleted(function (self $p) use ($recalc) {
            // Cascade 1:1 ke baris masuk tertaut (BULK, timestamp-match) — tak memicu event.
            if (! $p->isForceDeleting() && $p->ref_masuk_id) {
                TransaksiKas::whereKey($p->ref_masuk_id)
                    ->whereNull('deleted_at')
                    ->update(['deleted_at' => $p->deleted_at]);
            }

            $recalc($p);
        });

        static::restoring(function (self $p) {
            $ts = $p->deleted_at;

            if ($p->ref_masuk_id) {
                // withTrashed(): baris masuk sedang ter-soft-delete, harus lolos global scope.
                TransaksiKas::withTrashed()
                    ->whereKey($p->ref_masuk_id)
                    ->where('deleted_at', $ts)
                    ->update(['deleted_at' => null]);
            }
        });

        static::restored($recalc);
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(TransaksiKas::class, 'transaksi_id');
    }

    public function refMasuk(): BelongsTo
    {
        return $this->belongsTo(TransaksiKas::class, 'ref_masuk_id');
    }
}
