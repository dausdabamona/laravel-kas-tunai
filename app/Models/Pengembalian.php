<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasMicrosecondTimestamps;
use App\Services\NotaService;
use App\Support\Cascade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

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
            // Cascade 1:1 ke baris masuk tertaut (timestamp-match) — tak memicu event.
            if (! $p->isForceDeleting() && $p->ref_masuk_id) {
                Cascade::softDelete(TransaksiKas::whereKey($p->ref_masuk_id), $p->deleted_at);
            }

            $recalc($p);
        });

        static::restoring(function (self $p) {
            if ($p->ref_masuk_id) {
                Cascade::restore(TransaksiKas::whereKey($p->ref_masuk_id), $p->deleted_at);
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

    public function lampiran(): MorphMany
    {
        return $this->morphMany(Lampiran::class, 'attachable');
    }
}
